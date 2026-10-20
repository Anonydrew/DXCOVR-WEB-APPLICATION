@extends('layouts.admin')

@section('title', 'Media library')

@section('content')
    <x-admin.page-header title="Media library" description="Images, videos and documents used on the public website. Private documents such as CVs are never stored here." />

    @can('media.upload')
        <form method="POST" action="{{ route('admin.media.store') }}" enctype="multipart/form-data" class="a-card a-dropzone mb-6 border-dashed p-5"
              x-data="mediaUploader(@js(route('admin.media.store')))"
              :data-dragging="dragging"
              @dragover.prevent="dragging = true" @dragleave.prevent="dragging = false"
              @drop.prevent="dragging = false; upload($event.dataTransfer.files)">
            @csrf
            <div class="flex flex-wrap items-center gap-4">
                <span class="grid size-11 place-items-center rounded-full bg-surface-2 text-lime"><x-icon name="upload" :size="20" /></span>
                <div class="min-w-0 flex-1">
                    <p class="font-semibold">Upload files</p>
                    <p class="text-xs a-subtle">
                        Images (JPG, PNG, WebP, GIF, AVIF, SVG) up to {{ round($limits['image']['max_kb'] / 1024) }} MB ·
                        Video (MP4, WebM) up to {{ round($limits['video']['max_kb'] / 1024) }} MB ·
                        PDF up to {{ round($limits['document']['max_kb'] / 1024) }} MB. Location data is removed from photos automatically.
                    </p>
                </div>
                <label class="a-btn a-btn-primary cursor-pointer">
                    <span x-text="uploading ? `Uploading ${done}/${total}…` : 'Choose files'">Choose files</span>
                    <input x-ref="file" type="file" name="file" class="sr-only" multiple accept="image/jpeg,image/png,image/webp,image/gif,image/avif,image/svg+xml,.svg,video/mp4,video/webm,application/pdf"
                           @change="upload($event.target.files)">
                </label>
                <noscript><button type="submit" class="a-btn">Upload</button></noscript>
            </div>
            <ul x-show="errors.length" x-cloak class="mt-3 space-y-1 text-sm text-danger" role="alert">
                <template x-for="error in errors"><li x-text="error"></li></template>
            </ul>
            @error('file')<p class="a-error mt-3">{{ $message }}</p>@enderror
        </form>
    @endcan

    <div class="a-card overflow-hidden">
        <x-admin.filters :action="route('admin.media.index')" placeholder="Search file names, titles and alt text">
            <div>
                <label for="filter-kind" class="sr-only">Type</label>
                <select id="filter-kind" name="kind" class="a-input">
                    <option value="">All types</option>
                    @foreach (\App\Enums\MediaKind::options() as $value => $label)
                        <option value="{{ $value }}" @selected(request('kind') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            @if ($folders->isNotEmpty())
                <div>
                    <label for="filter-folder" class="sr-only">Folder</label>
                    <select id="filter-folder" name="folder" class="a-input">
                        <option value="">All folders</option>
                        @foreach ($folders as $folder)
                            <option value="{{ $folder }}" @selected(request('folder') === $folder)>{{ $folder }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="missing_alt" value="1" class="a-checkbox !mt-0" @checked(request('missing_alt'))> Missing alt text
            </label>
        </x-admin.filters>

        @if ($items->isEmpty())
            <div class="p-6"><x-admin.empty-state title="No media found" icon="image">Upload original imagery, product screenshots and documents to use across the website.</x-admin.empty-state></div>
        @else
            <ul class="grid grid-cols-2 gap-4 p-4 sm:grid-cols-4 lg:grid-cols-6 2xl:grid-cols-8">
                @foreach ($items as $item)
                    <li>
                        <a href="{{ route('admin.media.show', $item) }}" class="group block">
                            <span class="a-thumb a-checker block group-hover:border-line-strong">
                                @if ($item->isImage())
                                    <img src="{{ $item->variantUrl(480) }}" alt="{{ $item->alt ?? '' }}" loading="lazy">
                                @else
                                    <span class="grid h-full place-items-center"><span class="a-mono a-muted">{{ strtoupper($item->extension) }}</span></span>
                                @endif
                                @if ($item->isImage() && blank($item->alt))
                                    <span class="absolute bottom-2 left-2 rounded bg-black/70 px-1.5 py-0.5 text-[0.65rem] font-semibold text-warning">No alt text</span>
                                @endif
                            </span>
                            <span class="mt-1.5 block truncate text-xs">{{ $item->displayName() }}</span>
                            <span class="block text-[0.7rem] a-subtle">{{ $item->humanSize() }}@if ($item->width) · {{ $item->width }}×{{ $item->height }}@endif</span>
                        </a>
                    </li>
                @endforeach
            </ul>
            {{ $items->links('admin.partials.pagination') }}
        @endif
    </div>
@endsection
