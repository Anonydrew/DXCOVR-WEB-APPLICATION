@extends('layouts.admin')

@section('title', $product->exists ? 'Edit product' : 'New product')

@section('content')
    <x-admin.page-header :title="$product->exists ? $product->name : 'New product'" :back="route('admin.products.index')">
        <x-slot:actions>
            @if ($product->exists)
                <x-admin.delete-button :action="route('admin.products.destroy', $product)" confirm="Delete this product permanently? Its FAQs will also be deleted." />
            @endif
        </x-slot:actions>
    </x-admin.page-header>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
        <form method="POST" action="{{ $product->exists ? route('admin.products.update', $product) : route('admin.products.store') }}" class="space-y-6" data-dirty-guard>
            @csrf
            @if ($product->exists) @method('PUT') @endif

            <div class="a-card a-card-body space-y-5">
                <x-admin.input name="name" label="Product name" :value="$product->name" required maxlength="160" id="f-title" />
                @include('admin.partials.slug', ['model' => $product, 'prefix' => '/products/'])
                <x-admin.input name="tagline" label="Tagline" :value="$product->tagline" maxlength="200" />
                <x-admin.textarea name="short_description" label="Short description" :value="$product->short_description" required rows="3" counter="500" recommended="200" />
                <x-admin.select name="product_status" label="Product status" :options="\App\Enums\ProductStatus::options()" :value="$product->product_status" required hint="Shown as a badge. Choose the status that is accurate today." />
            </div>

            <div class="a-card a-card-body space-y-6">
                <h2 class="a-card-title">Content</h2>
                <x-admin.rich-editor name="body" label="Overview" :value="$product->body" />
                <x-admin.rich-editor name="audience" label="Who it is for" :value="$product->audience" compact />
                <x-admin.repeater name="features" label="Features" :rows="$product->features ?? []" :fields="['title' => ['label' => 'Feature'], 'body' => ['label' => 'Description', 'type' => 'textarea']]" :max="24" add-label="Add feature" />
                <x-admin.repeater name="modules" label="Modules" :rows="$product->modules ?? []" :fields="['title' => ['label' => 'Module'], 'body' => ['label' => 'Description', 'type' => 'textarea']]" :max="24" add-label="Add module" />
                <x-admin.list-field name="integrations" label="Available integrations" :items="$product->integrations" placeholder="e.g. Mobile money payments" />
                <x-admin.input name="documentation_url" label="Documentation link" type="url" :value="$product->documentation_url" />
            </div>

            <div class="a-card a-card-body space-y-6">
                <h2 class="a-card-title">Visuals</h2>
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-admin.media-field name="logo_media_id" label="Logo" :media="$product->logo" />
                    <x-admin.media-field name="hero_media_id" label="Hero image" :media="$product->heroImage" />
                </div>
                <x-admin.gallery-field name="screenshots" label="Screenshots" :attachments="$product->exists ? $product->mediaCollection('screenshots') : collect()" hint="Use real product screenshots. Remove any personal or confidential data before uploading." />
            </div>

            <div class="a-card a-card-body space-y-5">
                <h2 class="a-card-title">Enquiries & pricing</h2>
                <x-admin.toggle name="demo_requests_enabled" label="Accept demo requests" :checked="$product->demo_requests_enabled" />
                <x-admin.toggle name="enquiries_enabled" label="Accept product questions" :checked="$product->enquiries_enabled" />
                <x-admin.toggle name="pricing_visible" label="Show pricing" :checked="$product->pricing_visible" hint="Only enable once pricing has been approved." />
                <x-admin.rich-editor name="pricing" label="Pricing information" :value="$product->pricing" compact />
            </div>

            <div class="a-card a-card-body grid gap-5 sm:grid-cols-2">
                <h2 class="a-card-title sm:col-span-2">Relationships</h2>
                <x-admin.checkbox-list name="services" label="Related services" :options="$services" :selected="$product->services?->modelKeys() ?? []" />
                <x-admin.checkbox-list name="projects" label="Related case studies" :options="$projects" :selected="$product->projects?->modelKeys() ?? []" />
            </div>

            <x-admin.seo-panel :seo="$product->seo" :fallback-title="$product->name" :fallback-description="$product->short_description" :url="$product->exists ? route('products.show', $product) : url('/products')" />

            <div class="a-card a-card-body">
                <x-admin.toggle name="is_featured" label="Feature on the homepage" :checked="$product->is_featured" />
            </div>

            @include('admin.partials.save-bar', ['label' => $product->exists ? 'Save changes' : 'Save draft', 'note' => 'Saving never changes the publication status.'])
        </form>

        <aside>
            <x-admin.publication-panel :model="$product" type="product" />
        </aside>
    </div>
@endsection
