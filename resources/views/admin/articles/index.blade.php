@extends('layouts.admin')

@section('title', 'Articles')

@section('content')
    <x-admin.page-header title="Articles" :description="$seesAll ? 'Insights, news and tutorials published on the website.' : 'Your articles. Submit drafts for review when they are ready.'">
        <x-slot:actions>
            @can('create', \App\Models\Article::class)
                <a href="{{ route('admin.articles.create') }}" class="a-btn a-btn-primary"><x-icon name="plus" :size="16" /> New article</a>
            @endcan
        </x-slot:actions>
    </x-admin.page-header>

    <div class="a-card overflow-hidden">
        <x-admin.filters :action="route('admin.articles.index')" placeholder="Search titles and content">
            <div>
                <label for="filter-status" class="sr-only">Status</label>
                <select id="filter-status" name="status" class="a-input">
                    <option value="">All statuses</option>
                    @foreach (\App\Enums\PublicationStatus::options() as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="filter-category" class="sr-only">Category</label>
                <select id="filter-category" name="category" class="a-input">
                    <option value="">All categories</option>
                    @foreach ($categories as $id => $name)
                        <option value="{{ $id }}" @selected((string) request('category') === (string) $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
        </x-admin.filters>

        @if ($articles->isEmpty())
            <div class="p-6">
                <x-admin.empty-state title="No articles found" icon="file">
                    @if (request()->hasAny(['q', 'status', 'category']))
                        Try adjusting the filters.
                    @else
                        Write the first article to start publishing insights.
                    @endif
                </x-admin.empty-state>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="a-table">
                    <thead>
                        <tr>
                            <th scope="col">Title</th>
                            <th scope="col">Status</th>
                            <th scope="col">Author</th>
                            <th scope="col">Categories</th>
                            <th scope="col">Updated</th>
                            <th scope="col"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($articles as $article)
                            <tr>
                                <td class="max-w-md">
                                    @can('update', $article)
                                        <a href="{{ route('admin.articles.edit', $article) }}" class="font-semibold hover:text-lime">{{ $article->title }}</a>
                                    @else
                                        <span class="font-semibold">{{ $article->title }}</span>
                                    @endcan
                                    @if ($article->is_featured)
                                        <span class="a-badge a-badge-info ml-1">Featured</span>
                                    @endif
                                    <p class="truncate text-xs a-subtle">/insights/{{ $article->slug }}</p>
                                </td>
                                <td>
                                    <x-admin.status-badge :status="$article->status" />
                                    @if ($article->status === \App\Enums\PublicationStatus::Scheduled)
                                        <p class="mt-1 text-xs text-info">{{ $article->published_at?->format('j M, H:i') }}</p>
                                    @endif
                                </td>
                                <td class="a-muted">{{ $article->bylineName() }}</td>
                                <td class="a-muted">{{ $article->categories->pluck('name')->implode(', ') ?: 'Not set' }}</td>
                                <td class="whitespace-nowrap a-muted" title="{{ $article->updated_at->format('j M Y H:i') }}">{{ $article->updated_at->diffForHumans() }}</td>
                                <td class="text-right">
                                    @if ($article->isPubliclyVisible())
                                        <a href="{{ $article->publicUrl() }}" class="a-icon-btn" target="_blank" rel="noopener" aria-label="View “{{ $article->title }}” on the site"><x-icon name="external" :size="16" /></a>
                                    @endif
                                    @can('update', $article)
                                        <a href="{{ route('admin.articles.edit', $article) }}" class="a-icon-btn" aria-label="Edit “{{ $article->title }}”"><x-icon name="edit" :size="16" /></a>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $articles->links('admin.partials.pagination') }}
        @endif
    </div>
@endsection
