<div x-data="mediaPickerDialog({ endpoint: @js(route('admin.media.picker')), uploadUrl: @js(auth()->user()->can('media.upload') ? route('admin.media.store') : null) })"
     @media-picker:open.window="onOpen($event.detail)">
    <dialog x-ref="dialog" class="a-dialog" aria-labelledby="media-dialog-title" @cancel.prevent="cancel()">
        <div class="flex max-h-[calc(100vh-2rem)] flex-col">
            <div class="flex flex-wrap items-center gap-3 border-b border-line p-4">
                <h2 id="media-dialog-title" class="font-display text-lg font-semibold">Media library</h2>
                <form class="ml-auto flex flex-1 flex-wrap items-center justify-end gap-2" @submit.prevent="load(true)">
                    <label class="sr-only" for="media-dialog-search">Search media</label>
                    <input id="media-dialog-search" type="search" x-model="query" class="a-input max-w-xs" placeholder="Search by name or alt text">
                    <button type="submit" class="a-btn">Search</button>
                    @can('media.upload')
                        <label class="a-btn a-btn-primary cursor-pointer" :aria-disabled="uploading">
                            <x-icon name="upload" :size="16" />
                            <span x-text="uploading ? 'Uploading…' : 'Upload'"></span>
                            <input type="file" class="sr-only" :multiple="multiple" @change="upload($event)" :accept="kind === 'image' ? 'image/jpeg,image/png,image/webp,image/gif,image/avif,image/svg+xml,.svg' : ''">
                        </label>
                    @endcan
                    <button type="button" class="a-icon-btn" @click="cancel()" aria-label="Close"><x-icon name="close" :size="18" /></button>
                </form>
            </div>

            <div class="flex-1 overflow-y-auto p-4">
                <p x-show="error" x-text="error" class="a-alert a-alert-error mb-4" role="alert"></p>
                <ul class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-6">
                    <template x-for="item in items" :key="item.id">
                        <li>
                            <button type="button" class="group block w-full text-left" @click="toggle(item)" :aria-pressed="isSelected(item)">
                                <span class="a-thumb a-checker block" :class="isSelected(item) && 'ring-2 ring-lime'">
                                    <template x-if="item.is_image"><img :src="item.thumb" :alt="item.alt || ''" loading="lazy"></template>
                                    <template x-if="! item.is_image"><span class="grid h-full place-items-center a-mono a-muted" x-text="item.extension"></span></template>
                                    <span x-show="isSelected(item)" class="absolute right-2 top-2 grid size-6 place-items-center rounded-full bg-lime text-ink"><x-icon name="check" :size="14" /></span>
                                    <span x-show="! item.alt && item.is_image" class="absolute bottom-2 left-2 rounded bg-black/70 px-1.5 py-0.5 text-[0.65rem] font-semibold text-warning">No alt text</span>
                                </span>
                                <span class="mt-1.5 block truncate text-xs a-muted" x-text="item.name"></span>
                            </button>
                        </li>
                    </template>
                </ul>
                <p x-show="! loading && items.length === 0" class="py-16 text-center a-muted">No media found. Upload a file to get started.</p>
                <p x-show="loading" class="py-6 text-center a-muted">Loading…</p>
                <div class="mt-4 text-center" x-show="hasMore && ! loading">
                    <button type="button" class="a-btn" @click="more()">Load more</button>
                </div>
            </div>

            <div class="flex items-center justify-between gap-3 border-t border-line p-4" x-show="multiple">
                <p class="text-sm a-muted"><span x-text="selected.length"></span> selected</p>
                <div class="flex gap-2">
                    <button type="button" class="a-btn" @click="cancel()">Cancel</button>
                    <button type="button" class="a-btn a-btn-primary" @click="confirm()" :disabled="selected.length === 0">Add selected</button>
                </div>
            </div>
        </div>
    </dialog>
</div>
