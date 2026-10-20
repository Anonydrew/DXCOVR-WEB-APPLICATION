@extends('layouts.admin')

@php $isSystem = $page->isSystem(); @endphp
@section('title', $page->exists ? ($page->system_key === 'home' ? 'Homepage' : $page->title) : 'New page')

@section('content')
    <x-admin.page-header
        :title="$page->exists ? ($page->system_key === 'home' ? 'Homepage' : $page->title) : 'New page'"
        :description="$isSystem ? 'This page is always live at '.(parse_url($page->publicUrl(), PHP_URL_PATH) ?: '/').'. Changes to sections appear as soon as they are saved and enabled.' : null"
        :back="route('admin.pages.index')">
        <x-slot:actions>
            @if ($page->exists)
                <a href="{{ $page->publicUrl() }}" target="_blank" rel="noopener" class="a-btn"><x-icon name="external" :size="16" /> View page</a>
            @endif
            @if ($page->exists && ! $isSystem)
                <x-admin.delete-button :action="route('admin.pages.destroy', $page)" confirm="Delete this page permanently?" />
            @endif
        </x-slot:actions>
    </x-admin.page-header>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
        <div class="space-y-6">
            <form method="POST" action="{{ $page->exists ? route('admin.pages.update', $page) : route('admin.pages.store') }}" class="space-y-6" data-dirty-guard>
                @csrf
                @if ($page->exists) @method('PUT') @endif

                <div class="a-card a-card-body space-y-5">
                    <x-admin.input name="title" :label="$isSystem ? 'Page heading' : 'Title'" :value="$page->title" required maxlength="200" />
                    @unless ($isSystem)
                        @include('admin.partials.slug', ['model' => $page, 'prefix' => url('/').'/'])
                        <x-admin.select name="template" label="Page type" :options="\App\Models\Page::TEMPLATES" :value="$page->template" required />
                    @endunless
                    <x-admin.textarea name="excerpt" :label="$isSystem ? 'Introduction' : 'Summary'" :value="$page->excerpt" rows="3" counter="600" recommended="200" hint="Shown under the page heading and used as the default search description." />
                    @unless ($isSystem)
                        <x-admin.rich-editor name="body" label="Content" :value="$page->body" />
                    @endunless
                </div>

                @if ($page->hasFeatureHero())
                    @php $heroDefaults = \App\Models\Page::FEATURE_HEROES[$page->system_key]; @endphp
                    <section class="a-card" aria-labelledby="hero-heading">
                        <div class="a-card-header"><h2 id="hero-heading" class="a-card-title">Hero</h2></div>
                        <div class="a-card-body grid gap-5 sm:grid-cols-2">
                            <x-admin.media-field name="hero_media_id" label="Background photo" :media="$page->heroImage" class="sm:col-span-2"
                                hint="A wide landscape photo (at least 2400 px wide). It is darkened so the heading stays readable. Without one, DXCOVR artwork is shown." />
                            <x-admin.input name="hero_eyebrow" label="Label above the heading" :value="$page->hero_eyebrow" maxlength="60" :hint="'Default: “'.$heroDefaults['eyebrow'].'”'" />
                            <div></div>
                            <x-admin.input name="hero_cta_label" label="Button text" :value="$page->hero_cta_label" maxlength="40" :hint="'Default: “'.$heroDefaults['cta_label'].'”'" />
                            <x-admin.input name="hero_cta_url" label="Button link" :value="$page->hero_cta_url" maxlength="500" hint="Leave empty to use the default destination." />
                        </div>
                    </section>
                @endif

                <x-admin.seo-panel :seo="$page->seo" :fallback-title="$page->title" :fallback-description="$page->excerpt" :url="$page->exists ? $page->publicUrl() : url('/')" />

                @include('admin.partials.save-bar', ['label' => $page->exists ? 'Save page' : 'Save draft'])
            </form>

            @if ($page->exists)
                <section class="space-y-4" aria-labelledby="sections-heading">
                    <div class="flex flex-wrap items-end justify-between gap-3">
                        <div>
                            <h2 id="sections-heading" class="text-xl font-semibold">Sections</h2>
                            <p class="text-sm a-muted">Predefined blocks displayed in this order. Drag to reorder; hidden sections are not shown on the website.</p>
                        </div>
                        <form method="POST" action="{{ route('admin.pages.sections.store', $page) }}" class="flex gap-2">
                            @csrf
                            <label for="new-section-type" class="sr-only">Section type</label>
                            <select id="new-section-type" name="type" class="a-input" required>
                                <option value="">Add a section…</option>
                                @foreach ($sectionTypes as $type => $label)
                                    <option value="{{ $type }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="a-btn">Add</button>
                        </form>
                    </div>

                    @if ($page->sections->isEmpty())
                        <x-admin.empty-state title="No sections yet" icon="layers">Add sections to build this page from approved layouts.</x-admin.empty-state>
                    @else
                        <ol class="space-y-3" x-data="sortableList(@js(route('admin.reorder', 'sections')))">
                            @foreach ($page->sections as $section)
                                @include('admin.pages.section', ['section' => $section])
                            @endforeach
                        </ol>
                    @endif
                </section>
            @endif
        </div>

        <aside class="space-y-6">
            @if ($isSystem)
                <div class="a-card a-card-body text-sm">
                    <p class="font-semibold">Core page</p>
                    <p class="mt-2 a-muted">Core pages cannot be unpublished or deleted. Hide individual sections instead.</p>
                </div>
            @else
                <x-admin.publication-panel :model="$page" type="page" />
            @endif
            <div class="a-card a-card-body text-sm a-muted">
                <p class="font-semibold text-text">Emphasis in headings</p>
                <p class="mt-2">Wrap words in asterisks to highlight them in lime, e.g. <code class="text-text">digital solutions *that work.*</code></p>
            </div>
        </aside>
    </div>
@endsection
