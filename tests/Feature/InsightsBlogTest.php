<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\ArticleCategory;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InsightsBlogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_index_leads_with_the_latest_featured_story_then_editors_pick_and_recent_stories(): void
    {
        $category = ArticleCategory::factory()->create(['name' => 'Engineering notes']);
        $lead = Article::factory()->published(now()->subDays(2))->create(['title' => 'Lead featured story', 'is_featured' => true]);
        $lead->categories()->attach($category);
        $pick = Article::factory()->published(now()->subDays(5))->create(['title' => 'Older featured story', 'is_featured' => true]);
        $newest = Article::factory()->published(now()->subDay())->create(['title' => 'Newest regular story']);

        $html = $this->get('/insights')
            ->assertOk()
            ->assertSee('has-overlay-header', false)
            ->assertSee('Continue reading')
            ->assertSee('href="'.route('insights.show', $lead).'" class="btn btn-white', false)
            ->assertSee('Editor’s Pick')
            ->assertSee('Recent stories')
            ->assertSee('All collections')
            ->assertSee('Engineering notes')
            ->getContent();

        $this->assertSame(1, substr_count($html, '>Lead featured story</a>'), 'The lead story is not repeated in the lists.');
        $this->assertStringContainsString('Older featured story', $html);
        $this->assertStringContainsString('Newest regular story', $html);
    }

    public function test_collection_dropdown_redirects_to_the_category_page(): void
    {
        $category = ArticleCategory::factory()->create(['name' => 'Field reports']);
        $article = Article::factory()->published()->create(['title' => 'A report from the field']);
        $article->categories()->attach($category);

        $this->get('/insights?category='.$category->slug)->assertRedirect(route('insights.category', $category));
        $this->get('/insights?category=does-not-exist')->assertRedirect(route('insights.index'));

        $this->get(route('insights.category', $category))
            ->assertOk()
            ->assertSee('<h1 id="recent-heading" class="blog-heading-xl mt-3">Field reports</h1>', false)
            ->assertSee('A report from the field')
            ->assertSee('value="'.$category->slug.'" selected', false)
            ->assertDontSee('has-overlay-header', false);
    }

    public function test_article_page_shows_breadcrumb_share_and_recent_posts(): void
    {
        $category = ArticleCategory::factory()->create(['name' => 'Design']);
        $article = Article::factory()->published(now()->subDay())->create(['title' => 'Designing for clarity']);
        $article->categories()->attach($category);
        $others = Article::factory()->count(3)->sequence(
            ['published_at' => now()->subDays(2)],
            ['published_at' => now()->subDays(3)],
            ['published_at' => now()->subDays(4)],
        )->published()->create();

        $this->get(route('insights.show', $article))
            ->assertOk()
            ->assertSee('aria-label="Breadcrumb"', false)
            ->assertSee('<li class="accent" aria-current="page">Designing for clarity</li>', false)
            ->assertSee('data-share-url="'.route('insights.show', $article).'"', false)
            ->assertSee('Recent posts')
            ->assertSee('View all recent stories')
            ->assertSee($others[0]->title)
            ->assertSee($others[2]->title);
    }
}
