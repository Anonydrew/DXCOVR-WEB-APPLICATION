@extends('layouts.admin')

@section('title', $article->exists ? 'Edit article' : 'New article')

@section('content')
    <x-admin.page-header :title="$article->exists ? $article->title : 'New article'" :back="route('admin.articles.index')">
        <x-slot:actions>
            @if ($canDelete)
                <x-admin.delete-button :action="route('admin.articles.destroy', $article)" confirm="Delete this article permanently? This cannot be undone." label="Delete" />
            @endif
        </x-slot:actions>
    </x-admin.page-header>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
        <form id="article-form" method="POST" action="{{ $article->exists ? route('admin.articles.update', $article) : route('admin.articles.store') }}" class="space-y-6"
              x-data="autosave({ key: 'dxcovr.article.{{ $article->id ?? 'new' }}', url: @js($article->exists && in_array($article->status, [\App\Enums\PublicationStatus::Draft, \App\Enums\PublicationStatus::InReview], true) ? route('admin.articles.autosave', $article) : null), serverUpdatedAt: {{ $article->updated_at?->timestamp ?? 0 }} })">
            @csrf
            @if ($article->exists)
                @method('PUT')
            @endif

            <div x-show="restorable" x-cloak class="a-alert a-alert-info">
                <x-icon name="history" :size="18" class="mt-0.5 flex-none text-info" />
                <div class="flex-1">
                    <p>There is a newer unsaved version of this article on this device.</p>
                    <div class="mt-2 flex gap-2">
                        <button type="button" class="a-btn a-btn-sm" @click="restore()">Restore it</button>
                        <button type="button" class="a-btn a-btn-sm a-btn-ghost" @click="discard()">Discard</button>
                    </div>
                </div>
            </div>

            <div class="a-card a-card-body space-y-5">
                <x-admin.input name="title" label="Title" :value="$article->title" required maxlength="200" />
                <div x-data="slugField('f-title', {{ $article->exists ? 'true' : 'false' }})">
                    <label for="f-slug" class="a-label">URL slug</label>
                    <div class="flex items-center gap-2">
                        <span class="text-sm a-subtle">/insights/</span>
                        <input id="f-slug" name="slug" x-ref="slug" @change="manual()" value="{{ old('slug', $article->slug) }}" class="a-input" maxlength="180" @error('slug') aria-invalid="true" @enderror>
                    </div>
                    <p class="a-hint">Generated from the title. Changing it after publishing breaks existing links, so add a redirect if you do.</p>
                    @error('slug')<p class="a-error">{{ $message }}</p>@enderror
                </div>
                <x-admin.textarea name="excerpt" label="Excerpt" :value="$article->excerpt" rows="3" counter="500" recommended="200" hint="Shown on listing cards and used as the default search description." />
                <x-admin.rich-editor name="body" label="Article body" :value="$article->body" placeholder="Write the article. Use Heading 2 and 3 to build the table of contents." />
                <p class="text-xs a-subtle" x-text="status" aria-live="polite"></p>
            </div>

            <x-admin.seo-panel :seo="$article->seo" :fallback-title="$article->title" :fallback-description="$article->excerpt" :url="$article->exists ? route('insights.show', $article) : url('/insights')" />

            <div class="a-card a-card-body grid gap-5 sm:grid-cols-2">
                <x-admin.checkbox-list name="categories" label="Categories" :options="$categories" :selected="$article->categories?->modelKeys() ?? []" class="sm:col-span-2" />
                <x-admin.input name="tags" label="Tags" :value="$article->tags?->pluck('name')->implode(', ')" hint="Comma separated, e.g. AI, WebXR, Laravel" class="sm:col-span-2" />
                <x-admin.media-field name="featured_media_id" label="Featured image" :media="$article->featuredImage" class="sm:col-span-2" />
            </div>

            <div class="a-card a-card-body grid gap-5 sm:grid-cols-2">
                <h2 class="a-card-title sm:col-span-2">Attribution & display</h2>
                @if ($authors->isNotEmpty())
                    <x-admin.select name="author_id" label="Author" :options="$authors" :value="$article->exists ? $article->author_id : auth()->id()" placeholder="No linked author (use the byline)" />
                @endif
                <x-admin.input name="byline" label="Custom byline" :value="$article->byline" hint="Overrides the author name, e.g. “DXCOVR Engineering”." maxlength="120" />
                <x-admin.toggle name="show_author" label="Show author" :checked="$article->show_author" />
                <x-admin.toggle name="show_toc" label="Show table of contents" :checked="$article->show_toc" hint="Shown when the article has at least two headings." />
                @if ($canPublish)
                    <x-admin.toggle name="is_featured" label="Feature on the Insights page" :checked="$article->is_featured" />
                @endif
            </div>

            <div class="sticky bottom-0 z-10 -mx-4 flex flex-wrap items-center gap-3 border-t border-line bg-ink/95 px-4 py-4 backdrop-blur sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
                <button type="submit" class="a-btn a-btn-primary">{{ $article->exists ? 'Save changes' : 'Save draft' }}</button>
                <p class="text-xs a-subtle">Saving never publishes. Use the publishing panel to make it live.</p>
            </div>
        </form>

        <aside class="space-y-6">
            <x-admin.publication-panel :model="$article" type="article" :can-publish="$canPublish" :can-submit="true" />
            @if ($article->exists && $article->submitted_at)
                <div class="a-card a-card-body text-sm a-muted">Submitted for review {{ $article->submitted_at->diffForHumans() }}.</div>
            @endif
        </aside>
    </div>
@endsection
