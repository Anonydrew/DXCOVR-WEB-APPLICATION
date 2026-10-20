import Sortable from 'sortablejs';
import { request } from './http';

/**
 * Media library integration: a shared picker dialog (Alpine store + dialog
 * component), single-image fields and ordered galleries.
 */

export function mediaStore() {
    return {
        resolver: null,
        options: { accept: 'image', multiple: false },

        open(options = {}) {
            this.options = { accept: 'image', multiple: false, ...options };
            window.dispatchEvent(new CustomEvent('media-picker:open', { detail: this.options }));

            return new Promise((resolve) => {
                this.resolver = resolve;
            });
        },

        resolve(items) {
            this.resolver?.(items);
            this.resolver = null;
        },
    };
}

export function mediaPickerDialog({ endpoint, uploadUrl }) {
    return {
        items: [],
        selected: [],
        query: '',
        kind: 'image',
        page: 1,
        hasMore: false,
        loading: false,
        uploading: false,
        error: '',
        multiple: false,

        onOpen(detail) {
            this.multiple = Boolean(detail.multiple);
            this.kind = detail.accept || 'image';
            this.selected = [];
            this.error = '';
            this.query = '';
            this.$refs.dialog.showModal();
            this.load(true);
        },

        async load(reset = false) {
            if (reset) {
                this.page = 1;
                this.items = [];
            }

            this.loading = true;
            this.error = '';

            try {
                const url = new URL(endpoint, window.location.origin);
                url.searchParams.set('page', this.page);
                if (this.kind) url.searchParams.set('kind', this.kind);
                if (this.query) url.searchParams.set('q', this.query);

                const data = await request(url.toString());
                this.items = this.items.concat(data.data);
                this.hasMore = Boolean(data.next_page_url);
            } catch (error) {
                this.error = error.message;
            } finally {
                this.loading = false;
            }
        },

        more() {
            this.page++;
            this.load();
        },

        isSelected(item) {
            return this.selected.some((selected) => selected.id === item.id);
        },

        toggle(item) {
            if (this.isSelected(item)) {
                this.selected = this.selected.filter((selected) => selected.id !== item.id);
                return;
            }

            this.selected = this.multiple ? [...this.selected, item] : [item];

            if (!this.multiple) this.confirm();
        },

        async upload(event) {
            const files = [...(event.target.files || [])];
            if (!files.length) return;

            this.uploading = true;
            this.error = '';

            for (const file of files) {
                const form = new FormData();
                form.append('file', file);

                try {
                    const data = await request(uploadUrl, { method: 'POST', body: form });
                    this.items.unshift(data.media);
                    this.toggle(data.media);
                } catch (error) {
                    this.error = `${file.name}: ${error.message}`;
                }
            }

            event.target.value = '';
            this.uploading = false;
        },

        confirm() {
            window.Alpine.store('media').resolve(this.selected);
            this.$refs.dialog.close();
        },

        cancel() {
            window.Alpine.store('media').resolve(null);
            this.$refs.dialog.close();
        },
    };
}

export function mediaField({ initial = null, accept = 'image' } = {}) {
    return {
        media: initial,

        async choose() {
            const selection = await window.Alpine.store('media').open({ accept, multiple: false });
            if (selection?.[0]) {
                this.media = selection[0];
                this.$dispatch('input');
            }
        },

        clear() {
            this.media = null;
            this.$dispatch('input');
        },
    };
}

export function galleryField({ items = [], accept = 'image' } = {}) {
    return {
        items,

        init() {
            Sortable.create(this.$refs.list, {
                handle: '.a-drag-handle',
                animation: 150,
                onEnd: (event) => {
                    const moved = this.items.splice(event.oldIndex, 1)[0];
                    this.items.splice(event.newIndex, 0, moved);
                },
            });
        },

        async add() {
            const selection = await window.Alpine.store('media').open({ accept, multiple: true });

            (selection || []).forEach((item) => {
                if (!this.items.some((existing) => existing.id === item.id)) {
                    this.items.push({ ...item, caption: '' });
                }
            });
        },

        remove(index) {
            this.items.splice(index, 1);
        },
    };
}
