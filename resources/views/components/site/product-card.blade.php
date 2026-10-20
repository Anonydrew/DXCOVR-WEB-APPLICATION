@props(['product', 'headingLevel' => 'h3'])
<article {{ $attributes->merge(['class' => 'card card-link group flex h-full flex-col']) }}>
    <div class="media-frame aspect-[16/10]">
        @if ($product->heroImage)
            <x-site.picture :media="$product->heroImage" sizes="(min-width: 1024px) 50vw, 100vw" class="h-full w-full object-cover" />
        @else
            <x-site.cover-art :seed="$product->slug" glyph="software" :label="$product->name" />
        @endif
    </div>
    <div class="flex flex-1 flex-col gap-3 p-6 sm:p-7">
        <div class="flex items-center gap-3">
            @if ($product->logo)
                <x-site.picture :media="$product->logo" sizes="48px" class="size-10 rounded-lg object-contain" alt="" />
            @endif
            <span class="badge {{ $product->product_status === \App\Enums\ProductStatus::Available ? 'badge-lime' : '' }}">{{ $product->product_status->label() }}</span>
        </div>
        <{{ $headingLevel }} class="heading-md">
            <a href="{{ route('products.show', $product) }}" class="stretched-link">{{ $product->name }}</a>
        </{{ $headingLevel }}>
        @if ($product->tagline)
            <p class="font-medium">{{ $product->tagline }}</p>
        @endif
        <p class="text-muted line-clamp-3">{{ $product->short_description }}</p>
        <span class="mt-auto inline-flex items-center gap-2 pt-3 text-sm font-semibold" aria-hidden="true">
            Explore the product <x-icon name="arrow-right" :size="16" class="btn-arrow accent" />
        </span>
    </div>
</article>
