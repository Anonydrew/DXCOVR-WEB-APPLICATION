@extends('layouts.admin')

@section('title', $media->displayName())

@section('content')
    <x-admin.page-header :title="$media->displayName()" :description="$media->kind->label().' · '.$media->humanSize().($media->width ? ' · '.$media->width.' × '.$media->height.' px' : '')" :back="route('admin.media.index')">
        <x-slot:actions>
            <button type="button" class="a-btn" data-copy="{{ $media->url() }}"><x-icon name="copy" :size="16" /> Copy URL</button>
            <a href="{{ $media->url() }}" class="a-btn" target="_blank" rel="noopener"><x-icon name="external" :size="16" /> Open</a>
            @can('media.delete')
                <x-admin.delete-button :action="route('admin.media.destroy', $media)" confirm="Delete this file permanently?" :disabled="$usages !== []" />
            @endcan
        </x-slot:actions>
    </x-admin.page-header>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_24rem]">
        <div class="space-y-6">
            <div class="a-card a-checker grid min-h-72 place-items-center overflow-hidden p-4">
                @if ($media->isImage())
                    <img src="{{ $media->variantUrl(1600) }}" alt="{{ $media->alt ?? '' }}" class="max-h-[32rem] rounded-lg object-contain">
                @elseif ($media->kind === \App\Enums\MediaKind::Video)
                    <video src="{{ $media->url() }}" controls class="max-h-[32rem] rounded-lg"></video>
                @else
                    <a href="{{ $media->url() }}" target="_blank" rel="noopener" class="a-btn"><x-icon name="file" :size="18" /> Open {{ strtoupper($media->extension) }}</a>
                @endif
            </div>

            <section class="a-card" aria-labelledby="usage-heading">
                <div class="a-card-header"><h2 id="usage-heading" class="a-card-title">Where it is used</h2><span class="text-xs a-subtle">{{ count($usages) }}</span></div>
                @if ($usages === [])
                    <p class="a-card-body text-sm a-muted">Not used anywhere. It can be deleted safely.</p>
                @else
                    <ul class="divide-y divide-line text-sm">
                        @foreach ($usages as $usage)
                            <li class="flex items-center justify-between gap-3 px-5 py-3">
                                <span><span class="a-subtle">{{ $usage['type'] }}:</span> {{ $usage['title'] }}</span>
                                @if ($usage['url'])
                                    <a href="{{ $usage['url'] }}" class="a-btn a-btn-sm">Open</a>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            @if (! empty($media->variants))
                <section class="a-card a-card-body text-sm" aria-label="Responsive versions">
                    <p class="a-mono a-subtle">Responsive WebP versions</p>
                    <p class="mt-2 a-muted">{{ collect($media->variants)->keys()->map(fn ($width) => $width.' px')->implode(' · ') }}</p>
                </section>
            @endif
        </div>

        <aside class="space-y-6">
            @can('media.upload')
                <form method="POST" action="{{ route('admin.media.update', $media) }}" class="a-card a-card-body space-y-4">
                    @csrf
                    @method('PUT')
                    <h2 class="a-card-title">Details</h2>
                    @if ($media->isImage())
                        <x-admin.textarea name="alt" label="Alternative text" :value="$media->alt" rows="3" counter="300" recommended="125" hint="Describe what the image shows for people who cannot see it. Leave empty only for purely decorative images." />
                    @endif
                    <x-admin.input name="title" label="Title" :value="$media->title" maxlength="255" />
                    <x-admin.textarea name="caption" label="Default caption" :value="$media->caption" rows="2" />
                    <x-admin.input name="folder" label="Folder" :value="$media->folder" hint="Optional, for organising the library." maxlength="80" />
                    <button type="submit" class="a-btn a-btn-primary w-full">Save details</button>
                </form>

                <form method="POST" action="{{ route('admin.media.replace', $media) }}" enctype="multipart/form-data" class="a-card a-card-body space-y-3">
                    @csrf
                    <h2 class="a-card-title">Replace file</h2>
                    <p class="text-sm a-muted">Upload a new {{ \Illuminate\Support\Str::lower($media->kind->label()) }}. Every page using this media updates automatically.</p>
                    <label for="replace-file" class="sr-only">Replacement file</label>
                    <input id="replace-file" type="file" name="file" required class="a-input">
                    @error('file')<p class="a-error">{{ $message }}</p>@enderror
                    <button type="submit" class="a-btn w-full" data-confirm="Replace this file everywhere it is used?">Upload replacement</button>
                </form>
            @endcan

            <dl class="a-card a-card-body space-y-2 text-sm">
                <div class="flex justify-between gap-3"><dt class="a-subtle">Original name</dt><dd class="truncate">{{ $media->original_name }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="a-subtle">Type</dt><dd>{{ $media->mime_type }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="a-subtle">Uploaded</dt><dd>{{ $media->created_at->format('j M Y') }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="a-subtle">By</dt><dd>{{ $media->uploader?->name ?? 'Not set' }}</dd></div>
            </dl>
        </aside>
    </div>
@endsection
