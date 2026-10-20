{{-- Opens the device share sheet where available, otherwise copies the link. --}}
<button type="button" class="blog-share" data-share-url="{{ $url }}" data-share-title="{{ $title }}" hidden>
    <span data-share-label>Share</span>
    <x-icon name="share" :size="18" />
</button>
