import Alpine from 'alpinejs';
import focus from '@alpinejs/focus';
import Sortable from 'sortablejs';
import richEditor from './admin/editor';
import { galleryField, mediaField, mediaPickerDialog, mediaStore } from './admin/media';
import { request } from './admin/http';

/**
 * DXCOVR admin behaviour. Alpine powers the interactive pieces; every action
 * still submits to server routes that validate and authorise independently.
 */

window.Alpine = Alpine;
Alpine.plugin(focus);

/* Toasts ---------------------------------------------------------------- */

Alpine.store('toast', {
    items: [],
    show(message, tone = 'success') {
        const id = Date.now() + Math.random();
        this.items.push({ id, message, tone });
        setTimeout(() => this.dismiss(id), 4500);
    },
    dismiss(id) {
        this.items = this.items.filter((item) => item.id !== id);
    },
});

Alpine.store('media', mediaStore());

/* Slug field ------------------------------------------------------------ */

Alpine.data('slugField', (sourceId, locked = false) => ({
    edited: locked,

    init() {
        const source = document.getElementById(sourceId);
        if (!source) return;

        source.addEventListener('input', () => {
            if (!this.edited) this.$refs.slug.value = this.slugify(source.value);
        });
    },

    slugify(value) {
        return value
            .toString()
            .normalize('NFKD')
            .replace(/[̀-ͯ]/g, '')
            .toLowerCase()
            .replace(/&/g, ' and ')
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '')
            .slice(0, 180);
    },

    manual() {
        this.edited = this.$refs.slug.value !== '';
        this.$refs.slug.value = this.slugify(this.$refs.slug.value);
    },
}));

/* Repeater -------------------------------------------------------------- */

Alpine.data('repeater', (rows = [], blank = {}, max = 50) => ({
    rows: rows.length ? rows : [],
    max,

    add() {
        if (this.rows.length >= this.max) return;
        this.rows.push(JSON.parse(JSON.stringify(blank)));
        this.$nextTick(() => {
            const last = this.$refs.rows?.lastElementChild?.querySelector('input, textarea, select');
            last?.focus();
        });
    },

    remove(index) {
        this.rows.splice(index, 1);
    },

    move(index, direction) {
        const target = index + direction;
        if (target < 0 || target >= this.rows.length) return;
        const [row] = this.rows.splice(index, 1);
        this.rows.splice(target, 0, row);
    },

    async chooseMedia(row, field) {
        const selection = await Alpine.store('media').open({ accept: 'image', multiple: false });
        const item = selection?.[0];
        if (!item) return;
        row[field] = item.id;
        row[`${field}__thumb`] = item.thumb;
    },
}));

/* List repeater for simple strings (capabilities, skills…) --------------- */

Alpine.data('listField', (items = []) => ({
    items: items.length ? items : [],
    draft: '',

    add() {
        const value = this.draft.trim();
        if (!value) return;
        this.items.push(value);
        this.draft = '';
    },

    remove(index) {
        this.items.splice(index, 1);
    },

    move(index, direction) {
        const target = index + direction;
        if (target < 0 || target >= this.items.length) return;
        const [item] = this.items.splice(index, 1);
        this.items.splice(target, 0, item);
    },
}));

/* Sortable lists that persist their order ------------------------------- */

// A list nested inside another sortable list needs its own handle and id
// attribute, e.g. { handle: '.js-section-handle', attribute: 'data-section-id' }.
Alpine.data('sortableList', (url, { handle = '.a-drag-handle', attribute = 'data-id' } = {}) => ({
    init() {
        Sortable.create(this.$el, {
            handle,
            animation: 150,
            draggable: `[${attribute}]`,
            onEnd: async () => {
                const ids = [...this.$el.querySelectorAll(`:scope > [${attribute}]`)].map((element) => element.getAttribute(attribute));

                try {
                    await request(url, { method: 'POST', json: { ids } });
                    Alpine.store('toast').show('Order saved');
                } catch (error) {
                    Alpine.store('toast').show(error.message, 'error');
                }
            },
        });
    },
}));

/* Editors and media ----------------------------------------------------- */

Alpine.data('richEditor', richEditor);
Alpine.data('mediaPickerDialog', mediaPickerDialog);
Alpine.data('mediaField', mediaField);
Alpine.data('galleryField', galleryField);

/* Media library bulk uploader ------------------------------------------- */

Alpine.data('mediaUploader', (url) => ({
    dragging: false,
    uploading: false,
    done: 0,
    total: 0,
    errors: [],

    async upload(fileList) {
        const files = [...(fileList || [])];
        if (!files.length) return;

        this.uploading = true;
        this.done = 0;
        this.total = files.length;
        this.errors = [];

        for (const file of files) {
            const data = new FormData();
            data.append('file', file);

            try {
                await request(url, { method: 'POST', body: data });
            } catch (error) {
                this.errors.push(`${file.name}: ${error.message}`);
            }

            this.done++;
        }

        this.uploading = false;
        if (this.$refs.file) this.$refs.file.value = '';

        if (this.errors.length === 0) {
            window.location.reload();
        }
    },
}));

/* Invoice editor: line items with live totals --------------------------- */

// Mirrors App\Services\Invoicing so the preview matches what the server saves.
// Amounts are worked out in minor units (pesewas, cents).
Alpine.data('invoiceForm', ({ items = [], discount = '', taxRate = '0', currency = '', clientCurrencies = {}, defaultCurrency = 'GHS', followClient = false } = {}) => {
    let nextKey = 0;
    const withKey = (item) => ({ description: '', quantity: '1', unit_price: '', ...item, key: nextKey++ });
    const number = (value) => {
        const parsed = parseFloat(String(value ?? '').replace(/[,\s]/g, ''));
        return Number.isFinite(parsed) ? parsed : 0;
    };

    return {
        items: (items.length ? items : [{}]).map(withKey),
        discount,
        taxRate,
        currency,

        add() {
            this.items.push(withKey({}));
            this.$nextTick(() => this.$refs.rows?.lastElementChild?.querySelector('textarea')?.focus());
        },

        remove(index) {
            if (this.items.length > 1) this.items.splice(index, 1);
        },

        clientChanged(id) {
            if (followClient) this.currency = clientCurrencies[id] || defaultCurrency;
        },

        lineAmount(item) {
            const quantity = Math.round(number(item.quantity) * 100) / 100;
            return Math.round(quantity * Math.round(number(item.unit_price) * 100));
        },

        get subtotal() {
            return this.items.reduce((sum, item) => sum + this.lineAmount(item), 0);
        },

        get discountMinor() {
            return Math.min(Math.max(0, Math.round(number(this.discount) * 100)), this.subtotal);
        },

        get tax() {
            return Math.round(((this.subtotal - this.discountMinor) * number(this.taxRate)) / 100);
        },

        get total() {
            return this.subtotal - this.discountMinor + this.tax;
        },

        format(minor) {
            const amount = (minor / 100).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            return `${(this.currency || '').toUpperCase()} ${amount}`.trim();
        },
    };
});

/* Character counter ----------------------------------------------------- */

Alpine.data('charCount', (max, recommended = null) => ({
    length: 0,
    max,
    recommended,

    init() {
        const field = this.$el.querySelector('input, textarea');
        if (!field) return;
        const update = () => (this.length = field.value.length);
        field.addEventListener('input', update);
        update();
    },

    get tone() {
        if (this.length > this.max) return 'text-danger';
        if (this.recommended && this.length > this.recommended) return 'text-warning';
        return 'a-subtle';
    },
}));

/* Article autosave ------------------------------------------------------ */

Alpine.data('autosave', ({ key, url = null, serverUpdatedAt = 0 }) => ({
    status: '',
    restorable: null,
    dirty: false,
    timer: null,

    init() {
        try {
            const saved = JSON.parse(window.localStorage.getItem(key) || 'null');
            if (saved && saved.savedAt > serverUpdatedAt * 1000 && saved.body !== this.field('body')?.value) {
                this.restorable = saved;
            }
        } catch {
            this.restorable = null;
        }

        this.$el.addEventListener('input', () => {
            this.dirty = true;
            clearTimeout(this.timer);
            this.timer = setTimeout(() => this.save(), 2500);
        });

        this.$el.addEventListener('submit', () => {
            this.dirty = false;
            this.clear();
        });

        window.addEventListener('beforeunload', (event) => {
            if (this.dirty) {
                event.preventDefault();
                event.returnValue = '';
            }
        });
    },

    field(name) {
        return this.$el.querySelector(`[name="${name}"]`);
    },

    snapshot() {
        return {
            title: this.field('title')?.value ?? '',
            excerpt: this.field('excerpt')?.value ?? '',
            body: this.field('body')?.value ?? '',
            savedAt: Date.now(),
        };
    },

    async save() {
        const data = this.snapshot();

        try {
            window.localStorage.setItem(key, JSON.stringify(data));
            this.status = 'Saved on this device';
        } catch {
            this.status = '';
        }

        if (!url) return;

        try {
            const response = await request(url, { method: 'POST', json: data });
            this.status = response.message;
        } catch (error) {
            this.status = error.status === 409 ? error.message : 'Autosave to server failed. Your changes are kept on this device.';
        }
    },

    restore() {
        if (!this.restorable) return;
        this.field('title').value = this.restorable.title;
        this.field('excerpt').value = this.restorable.excerpt;
        this.$dispatch('restore-body', { html: this.restorable.body });
        this.restorable = null;
        this.dirty = true;
    },

    discard() {
        this.clear();
        this.restorable = null;
    },

    clear() {
        try {
            window.localStorage.removeItem(key);
        } catch {
            /* storage unavailable */
        }
    },
}));

/* Unsaved-changes guard for regular forms -------------------------------- */

function initDirtyGuards() {
    document.querySelectorAll('form[data-dirty-guard]').forEach((form) => {
        let dirty = false;
        form.addEventListener('input', () => (dirty = true));
        form.addEventListener('change', () => (dirty = true));
        form.addEventListener('submit', () => (dirty = false));
        window.addEventListener('beforeunload', (event) => {
            if (dirty) {
                event.preventDefault();
                event.returnValue = '';
            }
        });
    });
}

/* Confirmation for destructive or irreversible actions ------------------ */

function initConfirmations() {
    document.addEventListener(
        'submit',
        (event) => {
            const form = event.target;
            const message = event.submitter?.dataset.confirm || form.dataset.confirm;

            if (message && !window.confirm(message)) {
                event.preventDefault();
                event.stopImmediatePropagation();
            }
        },
        true,
    );
}

/* Copy to clipboard ----------------------------------------------------- */

function initCopyButtons() {
    document.addEventListener('click', async (event) => {
        const button = event.target.closest('[data-copy]');
        if (!button) return;

        try {
            await navigator.clipboard.writeText(button.dataset.copy);
            Alpine.store('toast').show('Copied to clipboard');
        } catch {
            Alpine.store('toast').show('Could not copy', 'error');
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initDirtyGuards();
    initConfirmations();
    initCopyButtons();
});

Alpine.start();
