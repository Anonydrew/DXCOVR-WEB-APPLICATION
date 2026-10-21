@if ($isPreview ?? false)
    <div class="sticky top-16 z-40 border-b border-warning/40 bg-[#2a2410] text-sm text-warning" role="status">
        <div class="container-dx flex flex-wrap items-center gap-3 py-2.5">
            <span class="badge border-warning/50 text-warning">Preview</span>
            <span>This is a private preview of unpublished content. It is not visible to the public or search engines.</span>
            @isset($status)
                <span class="ml-auto mono-label">Status: {{ $status }}</span>
            @endisset
        </div>
    </div>
@endif
