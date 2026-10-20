<?php

namespace Tests\Feature;

use App\Enums\PublicationStatus;
use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\Media;
use App\Models\User;
use App\Notifications\ContentSubmittedForReview;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ArticleWorkflowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    protected function payload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Designing dependable systems',
            'slug' => '',
            'excerpt' => 'A practical look at reliability.',
            'body' => '<h2>Why it matters</h2><p>Reliable systems earn trust.</p>',
            'show_author' => '1',
            'show_toc' => '1',
            'categories' => [ArticleCategory::factory()->create()->id],
            'tags' => 'Reliability, Architecture',
        ], $overrides);
    }

    public function test_editor_creates_a_draft_that_is_not_public(): void
    {
        $editor = $this->actingAsRole('Editor');

        $response = $this->post('/admin/articles', $this->payload());

        $article = Article::query()->firstOrFail();
        $response->assertRedirect(route('admin.articles.edit', $article));

        $this->assertSame('designing-dependable-systems', $article->slug);
        $this->assertSame(PublicationStatus::Draft, $article->status);
        $this->assertSame($editor->id, $article->author_id);
        $this->assertSame(['Architecture', 'Reliability'], $article->tags->pluck('name')->sort()->values()->all());
        $this->assertDatabaseHas('audit_logs', ['action' => 'content.created', 'auditable_id' => $article->id]);

        $this->get(route('insights.show', $article))->assertNotFound();
    }

    public function test_saving_never_publishes(): void
    {
        $this->actingAsRole('Editor');
        $article = Article::factory()->draft()->create();

        $this->put(route('admin.articles.update', $article), $this->payload(['status' => 'published', 'published_at' => now()->toDateTimeString()]));

        $this->assertSame(PublicationStatus::Draft, $article->fresh()->status);
        $this->assertNull($article->fresh()->published_at);
    }

    public function test_slugs_are_unique_and_validated(): void
    {
        $this->actingAsRole('Editor');
        Article::factory()->create(['slug' => 'taken-slug']);

        $this->post('/admin/articles', $this->payload(['slug' => 'taken-slug']))->assertSessionHasErrors('slug');
        $this->post('/admin/articles', $this->payload(['slug' => 'Not A Slug!']))->assertSessionHasErrors('slug');

        $this->post('/admin/articles', $this->payload(['title' => 'Taken slug']));
        $this->assertDatabaseHas('articles', ['slug' => 'taken-slug-2']);
    }

    public function test_rich_text_is_sanitised(): void
    {
        $this->actingAsRole('Editor');

        $this->post('/admin/articles', $this->payload([
            'body' => '<p onclick="steal()">Hello</p><script>alert(1)</script><a href="javascript:alert(1)">x</a><iframe src="https://evil.example/x"></iframe><iframe src="https://www.youtube-nocookie.com/embed/abcdefghijk"></iframe>',
        ]));

        $body = Article::query()->firstOrFail()->body;

        $this->assertStringNotContainsString('<script', $body);
        $this->assertStringNotContainsString('onclick', $body);
        $this->assertStringNotContainsString('javascript:', $body);
        $this->assertStringNotContainsString('evil.example', $body);
        $this->assertStringContainsString('youtube-nocookie.com/embed/abcdefghijk', $body);
    }

    public function test_publish_is_blocked_until_content_is_complete(): void
    {
        $this->actingAsRole('Editor');
        $article = Article::factory()->draft()->create(['excerpt' => null]);

        $this->post(route('admin.publication', ['article', $article->id]), ['action' => 'publish'])
            ->assertSessionHasErrors('publication');

        $this->assertSame(PublicationStatus::Draft, $article->fresh()->status);
    }

    public function test_publish_requires_alt_text_on_the_featured_image(): void
    {
        $this->actingAsRole('Editor');
        $article = Article::factory()->draft()->create(['featured_media_id' => Media::factory()->withoutAlt()->create()->id]);
        $article->categories()->attach(ArticleCategory::factory()->create());

        $this->post(route('admin.publication', ['article', $article->id]), ['action' => 'publish'])
            ->assertSessionHasErrors('publication');
    }

    public function test_editor_publishes_unpublishes_and_archives(): void
    {
        $this->actingAsRole('Editor');
        $article = Article::factory()->draft()->create();
        $article->categories()->attach(ArticleCategory::factory()->create());

        $this->post(route('admin.publication', ['article', $article->id]), ['action' => 'publish'])
            ->assertRedirect(route('admin.articles.edit', $article));

        $article->refresh();
        $this->assertSame(PublicationStatus::Published, $article->status);
        $this->assertNotNull($article->published_at);
        $this->get(route('insights.show', $article))->assertOk();

        $this->post(route('admin.publication', ['article', $article->id]), ['action' => 'unpublish']);
        $this->assertSame(PublicationStatus::Draft, $article->fresh()->status);
        $this->get(route('insights.show', $article))->assertNotFound();

        $this->post(route('admin.publication', ['article', $article->id]), ['action' => 'archive']);
        $this->assertSame(PublicationStatus::Archived, $article->fresh()->status);

        $this->assertDatabaseHas('audit_logs', ['action' => 'content.published', 'auditable_id' => $article->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'content.archived', 'auditable_id' => $article->id]);
    }

    public function test_scheduled_articles_are_published_by_the_scheduler_command(): void
    {
        $this->actingAsRole('Editor');
        $article = Article::factory()->draft()->create();
        $article->categories()->attach(ArticleCategory::factory()->create());

        $this->post(route('admin.publication', ['article', $article->id]), ['action' => 'schedule', 'publish_at' => now()->subMinute()->format('Y-m-d\TH:i')])
            ->assertSessionHasErrors('publish_at');

        $publishAt = now()->addHour()->startOfMinute();
        $this->post(route('admin.publication', ['article', $article->id]), ['action' => 'schedule', 'publish_at' => $publishAt->format('Y-m-d\TH:i')]);

        $article->refresh();
        $this->assertSame(PublicationStatus::Scheduled, $article->status);
        $this->get(route('insights.show', $article))->assertNotFound();

        $this->artisan('content:publish-scheduled')->assertSuccessful();
        $this->assertSame(PublicationStatus::Scheduled, $article->fresh()->status, 'Must not publish early.');

        $this->travelTo($publishAt->copy()->addMinute());
        $this->artisan('content:publish-scheduled')->assertSuccessful();

        $this->assertSame(PublicationStatus::Published, $article->fresh()->status);
        $this->get(route('insights.show', $article))->assertOk();
        $this->assertDatabaseHas('audit_logs', ['action' => 'content.auto_published', 'auditable_id' => $article->id]);
    }

    public function test_scheduled_publish_command_is_registered_with_the_scheduler(): void
    {
        $this->artisan('schedule:list')->expectsOutputToContain('content:publish-scheduled')->assertSuccessful();
    }

    public function test_author_cannot_publish_but_can_submit_for_review(): void
    {
        Notification::fake();
        $editor = User::factory()->withRole('Editor')->create();
        $author = $this->actingAsRole('Author');

        $this->post('/admin/articles', $this->payload());
        $article = Article::query()->firstOrFail();

        $this->post(route('admin.publication', ['article', $article->id]), ['action' => 'publish'])->assertForbidden();

        $this->post(route('admin.publication', ['article', $article->id]), ['action' => 'submit'])
            ->assertRedirect(route('admin.articles.edit', $article));

        $this->assertSame(PublicationStatus::InReview, $article->fresh()->status);
        Notification::assertSentTo($editor, ContentSubmittedForReview::class);
        Notification::assertNotSentTo($author, ContentSubmittedForReview::class);
    }

    public function test_author_cannot_edit_other_peoples_articles_or_published_work(): void
    {
        $someoneElses = Article::factory()->draft()->create(['author_id' => User::factory()->create()->id]);
        $this->actingAsRole('Author');

        $this->get(route('admin.articles.edit', $someoneElses))->assertForbidden();
        $this->put(route('admin.articles.update', $someoneElses), $this->payload())->assertForbidden();
        $this->delete(route('admin.articles.destroy', $someoneElses))->assertForbidden();

        $mine = Article::factory()->published()->create(['author_id' => auth()->id()]);
        $this->get(route('admin.articles.edit', $mine))->assertForbidden();
    }

    public function test_author_only_sees_their_own_articles(): void
    {
        Article::factory()->create(['title' => 'Someone else wrote this']);
        $author = $this->actingAsRole('Author');
        Article::factory()->create(['title' => 'My own draft', 'author_id' => $author->id]);

        $this->get('/admin/articles')->assertOk()->assertSee('My own draft')->assertDontSee('Someone else wrote this');
    }

    public function test_autosave_updates_drafts_but_never_live_articles(): void
    {
        $this->actingAsRole('Editor');
        $draft = Article::factory()->draft()->create();
        $live = Article::factory()->published()->create(['title' => 'Live title']);

        $this->postJson(route('admin.articles.autosave', $draft), ['title' => 'Autosaved title', 'body' => '<p>Work in progress</p>'])
            ->assertOk();
        $this->assertSame('Autosaved title', $draft->fresh()->title);

        $this->postJson(route('admin.articles.autosave', $live), ['title' => 'Should not change'])
            ->assertStatus(409);
        $this->assertSame('Live title', $live->fresh()->title);
    }

    public function test_preview_links_are_signed_and_not_indexable(): void
    {
        $this->actingAsRole('Editor');
        $article = Article::factory()->draft()->create(['title' => 'Preview me']);

        $response = $this->post(route('admin.publication', ['article', $article->id]), ['action' => 'preview']);
        $previewUrl = $response->headers->get('Location');

        $this->assertStringContainsString('signature=', $previewUrl);

        auth()->logout();

        $this->get($previewUrl)
            ->assertOk()
            ->assertSee('Preview me')
            ->assertSee('private preview')
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive');

        $this->get(route('preview', ['article', $article->id]))->assertForbidden();
    }

    public function test_deleting_an_article_is_audited(): void
    {
        $this->actingAsRole('Editor');
        $article = Article::factory()->create();

        $this->delete(route('admin.articles.destroy', $article))->assertRedirect(route('admin.articles.index'));

        $this->assertModelMissing($article);
        $this->assertDatabaseHas('audit_logs', ['action' => 'content.deleted']);
    }
}
