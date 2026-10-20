<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PublicationStatus;
use App\Http\Requests\Admin\ArticleRequest;
use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\Tag;
use App\Models\User;
use App\Services\HtmlSanitizer;
use App\Services\RichContent;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class ArticleController extends AdminController
{
    public function __construct(protected HtmlSanitizer $sanitizer, protected RichContent $content) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Article::class);

        $user = $request->user();
        $seesAll = $user->can('viewAll', Article::class);

        $articles = Article::query()
            ->with(['author', 'categories', 'editor'])
            ->when(! $seesAll, fn (Builder $query) => $query->where(fn (Builder $query) => $query->where('author_id', $user->id)->orWhere('created_by', $user->id)))
            ->withStatus($request->query('status'))
            ->when($request->query('category'), fn (Builder $query, $id) => $query->whereHas('categories', fn (Builder $query) => $query->whereKey($id)))
            ->search($request->string('q')->limit(100, '')->toString() ?: null)
            ->latest('updated_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.articles.index', [
            'articles' => $articles,
            'categories' => ArticleCategory::query()->orderBy('sort_order')->pluck('name', 'id'),
            'seesAll' => $seesAll,
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Article::class);

        $article = new Article(['show_author' => true, 'show_toc' => true]);

        return view('admin.articles.form', $this->formData($article));
    }

    public function store(ArticleRequest $request): RedirectResponse
    {
        Gate::authorize('create', Article::class);

        $article = new Article;

        $warning = DB::transaction(function () use ($request, $article) {
            $this->fillArticle($article, $request);
            $article->save();
            $this->syncRelations($article, $request);

            return $this->saveSeo($article, $request->validated('seo'));
        });

        $this->audit()->record('content.created', $article, 'Created article “'.$article->title.'”');

        return $this->saved('admin.articles.edit', [$article], 'Draft saved. It is not visible on the website until you publish it.', $warning);
    }

    public function edit(Article $article): View
    {
        Gate::authorize('update', $article);

        $article->load(['categories', 'tags', 'featuredImage', 'seo.ogImage', 'author', 'editor']);

        return view('admin.articles.form', $this->formData($article));
    }

    public function update(ArticleRequest $request, Article $article): RedirectResponse
    {
        Gate::authorize('update', $article);

        $warning = DB::transaction(function () use ($request, $article) {
            $this->fillArticle($article, $request);
            $article->save();
            $this->syncRelations($article, $request);

            return $this->saveSeo($article, $request->validated('seo'));
        });

        $this->audit()->record('content.updated', $article, 'Updated article “'.$article->title.'”', $this->audit()->changes($article));

        $message = $article->status === PublicationStatus::Published
            ? 'Changes saved and live on the website.'
            : 'Changes saved. The article is still a '.Str::lower($article->status->label()).'.';

        return $this->saved('admin.articles.edit', [$article], $message, $warning);
    }

    /**
     * Server-side autosave for drafts only. Published and scheduled articles
     * are never changed by autosave, so nothing goes live by accident.
     */
    public function autosave(Request $request, Article $article): JsonResponse
    {
        Gate::authorize('update', $article);

        if (! in_array($article->status, [PublicationStatus::Draft, PublicationStatus::InReview], true)) {
            return response()->json(['message' => 'Autosave is paused for live and scheduled articles. Use “Save changes” to update the published version.'], 409);
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'body' => ['nullable', 'string', 'max:1000000'],
        ]);

        $article->title = $data['title'];
        $article->excerpt = $data['excerpt'] ?? null;
        $article->body = $this->sanitizer->rich($data['body'] ?? null);
        $article->reading_time = $this->content->readingTime($article->body);
        $article->save();

        return response()->json(['message' => 'Draft autosaved at '.now()->format('H:i')]);
    }

    public function destroy(Article $article): RedirectResponse
    {
        Gate::authorize('delete', $article);

        $title = $article->title;
        $article->delete();

        $this->audit()->record('content.deleted', null, 'Deleted article “'.$title.'”');

        return redirect()->route('admin.articles.index')->with('status', 'Article deleted.');
    }

    protected function fillArticle(Article $article, ArticleRequest $request): void
    {
        $user = $request->user();
        $data = $request->safe()->only(['title', 'slug', 'excerpt', 'featured_media_id', 'byline']);

        $article->fill($data);
        $article->body = $this->sanitizer->rich($request->validated('body'));
        $article->show_author = $request->boolean('show_author');
        $article->show_toc = $request->boolean('show_toc');
        $article->reading_time = $this->content->readingTime($article->body);

        if ($user->can('articles.publish')) {
            $article->is_featured = $request->boolean('is_featured');
        }

        if ($user->can('changeAuthor', Article::class) && $request->filled('author_id')) {
            $article->author_id = (int) $request->validated('author_id');
        } elseif (! $article->exists) {
            $article->author_id = $user->id;
        }
    }

    protected function syncRelations(Article $article, ArticleRequest $request): void
    {
        $article->categories()->sync($request->validated('categories') ?? []);
        $article->tags()->sync(Tag::idsFromNames($request->validated('tags')));
    }

    /**
     * @return array<string, mixed>
     */
    protected function formData(Article $article): array
    {
        $user = request()->user();

        return [
            'article' => $article,
            'categories' => ArticleCategory::query()->orderBy('sort_order')->pluck('name', 'id'),
            'authors' => $user->can('changeAuthor', Article::class)
                ? User::query()->active()->orderBy('name')->pluck('name', 'id')
                : collect(),
            'canPublish' => $user->can('publish', $article),
            'canDelete' => $article->exists && $user->can('delete', $article),
        ];
    }
}
