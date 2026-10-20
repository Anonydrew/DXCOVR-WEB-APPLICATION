<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ProductStatus;
use App\Http\Requests\Admin\ProductRequest;
use App\Models\Product;
use App\Models\Project;
use App\Models\Service;
use App\Services\HtmlSanitizer;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends AdminController
{
    public function __construct(protected HtmlSanitizer $sanitizer) {}

    public function index(Request $request): View
    {
        return view('admin.products.index', [
            'products' => Product::query()->with('logo')->withCount('projects')->ordered()->get(),
            'canManage' => $request->user()->can('products.manage'),
        ]);
    }

    public function create(): View
    {
        return view('admin.products.form', $this->formData(new Product([
            'product_status' => ProductStatus::InDevelopment,
            'demo_requests_enabled' => true,
            'enquiries_enabled' => true,
        ])));
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        $product = new Product;
        $product->sort_order = (int) Product::query()->max('sort_order') + 1;

        $warning = DB::transaction(function () use ($request, $product) {
            $this->fill($product, $request);
            $product->save();
            $this->syncRelations($product, $request);

            return $this->saveSeo($product, $request->validated('seo'));
        });

        $this->audit()->record('content.created', $product, 'Created product “'.$product->name.'”');

        return $this->saved('admin.products.edit', [$product], 'Product saved as a draft.', $warning);
    }

    public function edit(Product $product): View
    {
        $product->load(['logo', 'heroImage', 'seo.ogImage', 'services', 'projects', 'mediaAttachments.media', 'editor']);

        return view('admin.products.form', $this->formData($product));
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $warning = DB::transaction(function () use ($request, $product) {
            $this->fill($product, $request);
            $product->save();
            $this->syncRelations($product, $request);

            return $this->saveSeo($product, $request->validated('seo'));
        });

        $this->audit()->record('content.updated', $product, 'Updated product “'.$product->name.'”', $this->audit()->changes($product));

        return $this->saved('admin.products.edit', [$product], 'Product updated.', $warning);
    }

    public function destroy(Product $product): RedirectResponse
    {
        $name = $product->name;

        DB::transaction(function () use ($product): void {
            $product->mediaAttachments()->delete();
            $product->delete();
        });

        $this->audit()->record('content.deleted', null, 'Deleted product “'.$name.'”');

        return redirect()->route('admin.products.index')->with('status', 'Product deleted.');
    }

    protected function fill(Product $product, ProductRequest $request): void
    {
        $product->fill($request->safe()->only([
            'name', 'slug', 'tagline', 'short_description', 'logo_media_id', 'hero_media_id', 'product_status', 'documentation_url',
        ]));
        $product->body = $this->sanitizer->rich($request->validated('body'));
        $product->audience = $this->sanitizer->rich($request->validated('audience'));
        $product->pricing = $this->sanitizer->rich($request->validated('pricing'));
        $product->features = $request->validated('features') ?: null;
        $product->modules = $request->validated('modules') ?: null;
        $product->integrations = $request->validated('integrations') ?: null;
        $product->demo_requests_enabled = $request->boolean('demo_requests_enabled');
        $product->enquiries_enabled = $request->boolean('enquiries_enabled');
        $product->pricing_visible = $request->boolean('pricing_visible');
        $product->is_featured = $request->boolean('is_featured');
    }

    protected function syncRelations(Product $product, ProductRequest $request): void
    {
        $product->services()->sync($request->validated('services') ?? []);
        $product->projects()->sync($request->validated('projects') ?? []);
        $product->syncMediaCollection('screenshots', $request->validated('screenshots') ?? []);
    }

    /**
     * @return array<string, mixed>
     */
    protected function formData(Product $product): array
    {
        return [
            'product' => $product,
            'services' => Service::query()->ordered()->pluck('title', 'id'),
            'projects' => Project::query()->orderBy('title')->pluck('title', 'id'),
        ];
    }
}
