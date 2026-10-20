@extends('layouts.admin')

@section('title', $service->exists ? 'Edit service' : 'New service')

@section('content')
    <x-admin.page-header :title="$service->exists ? $service->title : 'New service'" :back="route('admin.services.index')">
        <x-slot:actions>
            @if ($service->exists)
                <x-admin.delete-button :action="route('admin.services.destroy', $service)" confirm="Delete this service? Its FAQs will be deleted and related projects and products will be unlinked." />
            @endif
        </x-slot:actions>
    </x-admin.page-header>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
        <form method="POST" action="{{ $service->exists ? route('admin.services.update', $service) : route('admin.services.store') }}" class="space-y-6" data-dirty-guard>
            @csrf
            @if ($service->exists) @method('PUT') @endif

            <div class="a-card a-card-body space-y-5">
                <x-admin.input name="title" label="Title" :value="$service->title" required maxlength="200" />
                @include('admin.partials.slug', ['model' => $service, 'prefix' => '/services/'])
                <x-admin.textarea name="short_description" label="Short description" :value="$service->short_description" required rows="3" counter="400" recommended="200" hint="Shown on service cards, the homepage and in search results." />
                <x-admin.select name="icon" label="Visual motif" :options="\App\Models\Service::ICONS" :value="$service->icon" hint="The abstract illustration used when no hero image is set." />
            </div>

            <div class="a-card a-card-body space-y-5">
                <h2 class="a-card-title">Content</h2>
                <x-admin.rich-editor name="body" label="Overview" :value="$service->body" />
                <x-admin.list-field name="capabilities" label="Capabilities" :items="$service->capabilities" placeholder="e.g. API development" :max="20" />
                <x-admin.repeater name="use_cases" label="Typical use cases" :rows="$service->use_cases ?? []" :fields="['title' => ['label' => 'Title', 'max' => 160], 'body' => ['label' => 'Description', 'type' => 'textarea']]" :max="12" add-label="Add use case" />
                <x-admin.repeater name="process_steps" label="Process steps" :rows="$service->process_steps ?? []" :fields="['title' => ['label' => 'Step', 'max' => 160], 'body' => ['label' => 'Description', 'type' => 'textarea']]" :max="8" add-label="Add step" />
            </div>

            <div class="a-card a-card-body grid gap-5 sm:grid-cols-2">
                <h2 class="a-card-title sm:col-span-2">Hero media</h2>
                <x-admin.media-field name="hero_media_id" label="Hero image" :media="$service->heroImage" class="sm:col-span-2" />
                <x-admin.input name="hero_video_url" label="Hero video (YouTube or Vimeo)" :value="$service->hero_video_url" type="url" class="sm:col-span-2" hint="Takes priority over the hero image when set." />
                <x-admin.media-field name="loop_video_media_id" label="Looping video for the services page (optional)" :media="$service->loopVideo" accept="video" class="sm:col-span-2"
                    hint="A short, silent MP4 (5 to 20 seconds, under 10 MB). It plays on a loop beside this service on the services page, with the hero image shown while it loads." />
            </div>

            <div class="a-card a-card-body grid gap-5 sm:grid-cols-2">
                <h2 class="a-card-title sm:col-span-2">Call to action</h2>
                <x-admin.input name="cta_heading" label="Heading" :value="$service->cta_heading" maxlength="160" class="sm:col-span-2" placeholder="Let’s build something that works." />
                <x-admin.textarea name="cta_body" label="Text" :value="$service->cta_body" rows="2" class="sm:col-span-2" />
                <x-admin.input name="cta_label" label="Button label" :value="$service->cta_label" maxlength="60" placeholder="Start a project" />
                <x-admin.input name="cta_url" label="Button destination" :value="$service->cta_url" placeholder="/contact" />
            </div>

            <x-admin.seo-panel :seo="$service->seo" :fallback-title="$service->title" :fallback-description="$service->short_description" :url="$service->exists ? route('services.show', $service) : url('/services')" />

            <div class="a-card a-card-body">
                <x-admin.toggle name="is_featured" label="Feature this service" :checked="$service->is_featured" hint="Featured services are listed first on the homepage." />
            </div>

            @include('admin.partials.save-bar', ['label' => $service->exists ? 'Save changes' : 'Save draft', 'note' => 'Saving never changes the publication status.'])
        </form>

        <aside>
            <x-admin.publication-panel :model="$service" type="service" />
        </aside>
    </div>
@endsection
