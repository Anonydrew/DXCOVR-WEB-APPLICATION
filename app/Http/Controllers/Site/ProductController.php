<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\Product;
use App\Services\RichContent;
use App\Support\Seo\Seo;
use Illuminate\Contracts\View\View;

class ProductController extends Controller
{
    public function __construct(protected RichContent $content) {}

    public function index(): View
    {
        $page = Page::system('products');

        return view('site.products.index', [
            'page' => $page,
            'products' => Product::query()->published()->with(['logo', 'heroImage'])->ordered()->get(),
            'seo' => Seo::make($page?->title ?? 'Products')
                ->description($page?->excerpt)
                ->meta($page?->seo)
                ->breadcrumbs([
                    ['name' => 'Home', 'url' => route('home')],
                    ['name' => 'Products', 'url' => route('products.index')],
                ]),
        ]);
    }

    public function show(Product $product): View
    {
        abort_unless($product->isPubliclyVisible(), 404);

        return $this->render($product);
    }

    public function render(Product $product, bool $preview = false): View
    {
        $product->load([
            'logo', 'heroImage', 'seo.ogImage', 'mediaAttachments.media',
            'faqs' => fn ($query) => $query->published(),
            'services' => fn ($query) => $query->published()->ordered(),
            'projects' => fn ($query) => $query->published()->with(['cover', 'category', 'services'])->ordered(),
        ]);

        return view('site.products.show', [
            'product' => $product,
            'body' => $this->content->prepare($product->body)['html'],
            'audience' => $this->content->prepare($product->audience)['html'],
            'pricing' => $product->pricing_visible ? $this->content->prepare($product->pricing)['html'] : null,
            'screenshots' => $product->mediaCollection('screenshots'),
            'isPreview' => $preview,
            'seo' => Seo::make($product->name.($product->tagline ? ': '.$product->tagline : ''))
                ->description($product->short_description)
                ->image($product->heroImage ?? $product->logo)
                ->meta($product->seo)
                ->noindex($preview)
                ->canonical($preview ? null : route('products.show', $product))
                ->breadcrumbs([
                    ['name' => 'Home', 'url' => route('home')],
                    ['name' => 'Products', 'url' => route('products.index')],
                    ['name' => $product->name, 'url' => route('products.show', $product)],
                ])
                ->schema(array_filter([
                    '@context' => 'https://schema.org',
                    '@type' => 'SoftwareApplication',
                    'name' => $product->name,
                    'description' => $product->short_description,
                    'applicationCategory' => 'BusinessApplication',
                    'image' => $product->heroImage?->variantUrl(1600),
                    'publisher' => ['@id' => url('/').'#organization'],
                    'url' => route('products.show', $product),
                ])),
        ]);
    }
}
