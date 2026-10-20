<section class="surface-dark section relative overflow-hidden" aria-labelledby="products-{{ $section->id }}">
    <div class="glow-lime pointer-events-none absolute -left-60 bottom-0 size-[40rem] opacity-50" aria-hidden="true"></div>
    <div class="container-dx relative">
        <x-site.section-heading
            :id="'products-'.$section->id"
            :eyebrow="$section->value('eyebrow')"
            :heading="$section->value('heading')"
            :intro="$section->value('intro')"
            :cta-label="$section->value('cta_label')"
            :cta-url="$section->value('cta_url')"
        />

        <div class="mt-14 space-y-6">
            @foreach ($data['products'] as $product)
                <article class="card card-link group grid overflow-hidden lg:grid-cols-12" data-reveal>
                    <div class="media-frame aspect-[16/10] lg:col-span-7 lg:aspect-auto lg:min-h-[28rem]">
                        @if ($product->heroImage)
                            <x-site.picture :media="$product->heroImage" sizes="(min-width: 1024px) 58vw, 100vw" class="h-full w-full object-cover" />
                        @else
                            <x-site.cover-art :seed="$product->slug" glyph="software" :label="$product->name" />
                        @endif
                    </div>
                    <div class="flex flex-col justify-center gap-5 p-7 sm:p-10 lg:col-span-5">
                        <div class="flex items-center gap-3">
                            @if ($product->logo)
                                <x-site.picture :media="$product->logo" sizes="48px" class="size-12 rounded-xl object-contain" alt="" />
                            @endif
                            <span class="badge {{ $product->product_status === \App\Enums\ProductStatus::Available ? 'badge-lime' : '' }}">{{ $product->product_status->label() }}</span>
                        </div>
                        <h3 class="display-md">
                            <a href="{{ route('products.show', $product) }}" class="stretched-link">{{ $product->name }}</a>
                        </h3>
                        @if ($product->tagline)
                            <p class="heading-md font-medium text-muted">{{ $product->tagline }}</p>
                        @endif
                        <p class="text-muted">{{ $product->short_description }}</p>
                        @if (! empty($product->modules))
                            <ul class="flex flex-wrap gap-2" aria-label="Modules">
                                @foreach (collect($product->modules)->take(6) as $module)
                                    <li class="chip">{{ $module['title'] }}</li>
                                @endforeach
                            </ul>
                        @endif
                        <span class="mt-2 inline-flex items-center gap-2 font-semibold" aria-hidden="true">
                            Explore {{ $product->name }} <x-icon name="arrow-right" :size="18" class="btn-arrow accent" />
                        </span>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</section>
