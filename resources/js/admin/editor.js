import { Editor } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';
import Image from '@tiptap/extension-image';
import Youtube from '@tiptap/extension-youtube';
import { Placeholder } from '@tiptap/extensions';
import { isSafeUrl } from './http';

/**
 * Rich text editor (Tiptap) bound to a hidden textarea.
 *
 * The editor instance is kept outside Alpine's reactive state (Alpine's
 * proxies break ProseMirror), and a counter triggers toolbar re-renders.
 * Output is sanitised again on the server; this UI only offers safe nodes.
 */
export default function richEditor({ placeholder = 'Start writing…', allowMedia = true } = {}) {
    let editor = null;

    return {
        tick: 0,
        allowMedia,
        wordCount: 0,

        init() {
            const input = this.$refs.input;

            editor = new Editor({
                element: this.$refs.content,
                content: input.value || '',
                extensions: [
                    StarterKit.configure({
                        heading: { levels: [2, 3, 4] },
                        link: {
                            openOnClick: false,
                            autolink: true,
                            defaultProtocol: 'https',
                            HTMLAttributes: { rel: 'noopener noreferrer', target: null },
                            isAllowedUri: (url) => isSafeUrl(url),
                        },
                    }),
                    Image.configure({ inline: false, allowBase64: false }),
                    Youtube.configure({ nocookie: true, controls: true, width: 1280, height: 720 }),
                    Placeholder.configure({ placeholder }),
                ],
                editorProps: {
                    attributes: {
                        class: 'prose-editor',
                        'aria-label': this.$el.dataset.label || 'Content editor',
                        role: 'textbox',
                        'aria-multiline': 'true',
                    },
                },
                onUpdate: () => {
                    input.value = editor.isEmpty ? '' : editor.getHTML();
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                    this.updateCount();
                    this.tick++;
                },
                onSelectionUpdate: () => this.tick++,
                onTransaction: () => this.tick++,
            });

            this.updateCount();
        },

        destroy() {
            editor?.destroy();
        },

        updateCount() {
            const text = editor?.getText() ?? '';
            this.wordCount = text.trim() === '' ? 0 : text.trim().split(/\s+/).length;
        },

        isActive(name, attributes = {}) {
            this.tick;
            return editor?.isActive(name, attributes) ?? false;
        },

        run(command, ...args) {
            editor?.chain().focus()[command](...args).run();
        },

        setHeading(level) {
            if (level === 0) {
                this.run('setParagraph');
            } else {
                this.run('toggleHeading', { level });
            }
        },

        currentBlock() {
            this.tick;
            for (const level of [2, 3, 4]) {
                if (editor?.isActive('heading', { level })) return String(level);
            }
            return '0';
        },

        link() {
            const previous = editor.getAttributes('link').href ?? '';
            const url = window.prompt('Link address (https://…, /page, mailto: or tel:). Leave empty to remove the link.', previous);

            if (url === null) return;

            if (url.trim() === '') {
                editor.chain().focus().extendMarkRange('link').unsetLink().run();
                return;
            }

            if (!isSafeUrl(url)) {
                window.alert('Please enter a link starting with https://, /, mailto: or tel:.');
                return;
            }

            editor.chain().focus().extendMarkRange('link').setLink({ href: url.trim() }).run();
        },

        async image() {
            const selection = await window.Alpine.store('media').open({ accept: 'image', multiple: false });
            const item = selection?.[0];

            if (!item) return;

            if (!item.alt) {
                window.alert('Tip: add alternative text to this image in the media library so it is accessible.');
            }

            editor.chain().focus().setImage({ src: item.url, alt: item.alt || '', title: item.title || null }).run();
        },

        video() {
            const url = window.prompt('YouTube or Vimeo link');
            if (!url) return;

            if (/youtu\.?be/.test(url)) {
                editor.chain().focus().setYoutubeVideo({ src: url }).run();
                return;
            }

            const vimeo = url.match(/vimeo\.com\/(?:video\/)?(\d{6,12})/);
            if (vimeo) {
                editor
                    .chain()
                    .focus()
                    .insertContent(`<iframe src="https://player.vimeo.com/video/${vimeo[1]}" title="Vimeo video" allowfullscreen></iframe>`)
                    .run();
                return;
            }

            window.alert('Only YouTube and Vimeo links can be embedded.');
        },

        setContent(html) {
            editor?.commands.setContent(html || '', { emitUpdate: true });
        },
    };
}
