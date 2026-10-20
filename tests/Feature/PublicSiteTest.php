<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\JobListing;
use App\Models\Page;
use App\Models\Product;
use App\Models\Project;
use App\Models\ProjectCategory;
use App\Models\Redirect;
use App\Models\Service;
use App\Models\Tag;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PublicSiteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function publicPages(): array
    {
        return [
            'home' => ['/'],
            'about' => ['/about'],
            'founder' => ['/about/founder'],
            'services' => ['/services'],
            'service detail' => ['/services/software-engineering-and-business-systems'],
            'work' => ['/work'],
            'products' => ['/products'],
            'product detail' => ['/products/dxcovr-clinic-erp'],
            'insights' => ['/insights'],
            'insights feed' => ['/insights/feed'],
            'careers' => ['/careers'],
            'contact' => ['/contact'],
            'search' => ['/search'],
            'search results' => ['/search?q=software'],
            'sitemap' => ['/sitemap.xml'],
            'robots' => ['/robots.txt'],
        ];
    }

    #[DataProvider('publicPages')]
    public function test_public_pages_render(string $path): void
    {
        $this->get($path)->assertOk();
    }

    public function test_homepage_renders_database_content_and_structured_data(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('We turn complex ideas into digital solutions', false)
            ->assertSee('Software Engineering and Business Systems')
            ->assertSee('DXCOVR Clinic ERP')
            ->assertSee('"@type":"Organization"', false)
            ->assertSee('<link rel="canonical" href="'.url('/').'">', false);
    }

    public function test_homepage_sections_can_be_hidden_from_the_cms(): void
    {
        $hero = Page::system('home')->sections()->where('type', 'hero')->first();
        $hero->update(['is_enabled' => false]);

        $this->get('/')->assertOk()->assertDontSee('We turn complex ideas into digital solutions', false);
    }

    public function test_draft_and_scheduled_content_is_not_public(): void
    {
        $draft = Article::factory()->draft()->create(['title' => 'Secret draft article']);
        $scheduled = Article::factory()->scheduled()->create(['title' => 'Future article']);
        $service = Service::factory()->draft()->create();
        $project = Project::factory()->draft()->create();
        $product = Product::factory()->draft()->create();
        $job = JobListing::factory()->draft()->create();

        $this->get(route('insights.show', $draft))->assertNotFound();
        $this->get(route('insights.show', $scheduled))->assertNotFound();
        $this->get(route('services.show', $service))->assertNotFound();
        $this->get(route('work.show', $project))->assertNotFound();
        $this->get(route('products.show', $product))->assertNotFound();
        $this->get(route('careers.show', $job))->assertNotFound();

        $this->get('/insights')->assertDontSee('Secret draft article')->assertDontSee('Future article');
    }

    public function test_legal_pages_are_seeded_as_drafts_and_hidden_from_navigation(): void
    {
        $this->assertSame('draft', Page::query()->where('slug', 'privacy-policy')->first()->status->value);

        $this->get('/privacy-policy')->assertNotFound();
        $this->get('/')->assertDontSee('href="'.url('/privacy-policy').'"', false);
    }

    public function test_published_custom_page_is_served_and_linked(): void
    {
        $page = Page::query()->where('slug', 'privacy-policy')->first();
        $page->status = 'published';
        $page->published_at = now()->subMinute();
        $page->save();

        $this->get('/privacy-policy')->assertOk()->assertSee('Privacy policy');
        $this->get('/')->assertSee('href="'.url('/privacy-policy').'"', false);
    }

    public function test_article_page_has_seo_metadata_toc_and_json_ld(): void
    {
        $category = ArticleCategory::factory()->create();
        $article = Article::factory()->published()->create([
            'title' => 'Building connected systems',
            'excerpt' => 'How hardware and software meet.',
            'body' => '<h2>First heading</h2><p>Text</p><h2>Second heading</h2><p>More</p>',
        ]);
        $article->categories()->attach($category);

        $this->get(route('insights.show', $article))
            ->assertOk()
            ->assertSee('<title>Building connected systems | DXCOVR</title>', false)
            ->assertSee('<meta name="description" content="How hardware and software meet.">', false)
            ->assertSee('<meta property="og:type" content="article">', false)
            ->assertSee('"@type":"BlogPosting"', false)
            ->assertSee('"@type":"BreadcrumbList"', false)
            ->assertSee('href="#first-heading"', false)
            ->assertSee('id="first-heading"', false);
    }

    public function test_seo_overrides_and_noindex_are_respected(): void
    {
        $article = Article::factory()->published()->create();
        $article->saveSeo(['meta_title' => 'Custom SEO title', 'noindex' => true, 'canonical_url' => 'https://example.org/original']);

        $this->get(route('insights.show', $article))
            ->assertSee('<title>Custom SEO title | DXCOVR</title>', false)
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false)
            ->assertSee('<link rel="canonical" href="https://example.org/original">', false);

        $this->get('/sitemap.xml')->assertDontSee(route('insights.show', $article));
    }

    public function test_sitemap_lists_only_published_content(): void
    {
        $published = Article::factory()->published()->create();
        $draft = Article::factory()->draft()->create();

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee(route('insights.show', $published))
            ->assertDontSee(route('insights.show', $draft))
            ->assertSee(route('services.show', 'software-engineering-and-business-systems'));
    }

    public function test_robots_blocks_admin_and_respects_discourage_indexing(): void
    {
        $this->get('/robots.txt')->assertOk()->assertSee('Disallow: /admin')->assertSee('Sitemap: '.route('sitemap'));

        $this->setting(['seo.discourage_indexing' => true]);

        $this->get('/robots.txt')->assertSee("Disallow: /\n", false);
        $this->get('/')->assertSee('<meta name="robots" content="noindex, nofollow">', false);
    }

    public function test_security_headers_are_sent(): void
    {
        $response = $this->get('/');

        $response->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');

        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("object-src 'none'", $csp);
        $this->assertStringNotContainsString('unsafe-eval', $csp, 'The public site must not allow eval.');
    }

    public function test_portfolio_filters_by_category_and_type(): void
    {
        $category = ProjectCategory::query()->where('name', 'Hardware and IoT')->first();
        $match = Project::factory()->published()->create(['title' => 'Sensor platform', 'project_category_id' => $category->id]);
        $other = Project::factory()->published()->create(['title' => 'Brand refresh']);

        $this->get('/work?category='.$category->slug)->assertOk()->assertSee($match->title)->assertDontSee($other->title);
        $this->get('/work?type=concept')->assertSee($match->title)->assertSee($other->title);
        $this->get('/work?category=does-not-exist')->assertOk()->assertSee('No projects match these filters');
    }

    public function test_portfolio_paginates(): void
    {
        Project::factory()->count(14)->published()->create();

        $this->get('/work')->assertOk()->assertSee('rel="next"', false);
        $this->get('/work?page=2')->assertOk()->assertSee('rel="prev"', false);
    }

    public function test_confidential_projects_hide_client_name(): void
    {
        $project = Project::factory()->forClient('Hidden Corp')->published()->create([
            'is_confidential' => true,
            'anonymised_client_label' => 'A logistics company',
        ]);

        $this->get(route('work.show', $project))->assertOk()->assertSee('A logistics company')->assertDontSee('Hidden Corp');
    }

    public function test_search_finds_published_content_only(): void
    {
        Article::factory()->published()->create(['title' => 'Quantum widgets explained']);
        Article::factory()->draft()->create(['title' => 'Quantum draft notes']);

        $this->get('/search?q=Quantum')->assertOk()->assertSee('Quantum widgets explained')->assertDontSee('Quantum draft notes');
        $this->get('/search?q=zzzznotfound')->assertOk()->assertSee('No results for');
    }

    public function test_insights_category_and_tag_pages(): void
    {
        $category = ArticleCategory::query()->where('slug', 'artificial-intelligence')->first();
        $article = Article::factory()->published()->create(['title' => 'Practical AI']);
        $article->categories()->attach($category);
        $article->tags()->attach(Tag::factory()->create(['name' => 'RAG']));

        $this->get(route('insights.category', $category))->assertOk()->assertSee('Practical AI');
        $this->get(route('insights.tag', 'rag'))->assertOk()->assertSee('Practical AI');
    }

    public function test_unknown_pages_return_the_custom_404(): void
    {
        $this->get('/this-page-does-not-exist')->assertNotFound()->assertSee('This page could not be found');
    }

    public function test_admin_redirect_rules_are_applied(): void
    {
        Redirect::query()->create(['from_path' => '/old-services', 'to_url' => '/services', 'status_code' => 301, 'is_active' => true]);

        $this->get('/old-services')->assertRedirect(url('/services'))->assertStatus(301);
        $this->get('/old-services/')->assertStatus(301);
        $this->assertSame(2, Redirect::query()->first()->hits);
    }
}
