<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\Page;
use App\Models\PageSection;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeatureHeroAndClientsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    protected function clients(): PageSection
    {
        return Page::system('home')->sections()->where('type', 'clients')->firstOrFail();
    }

    public function test_services_page_opens_with_the_photo_hero_under_a_transparent_header(): void
    {
        $this->get('/services')
            ->assertOk()
            ->assertSee('has-overlay-header', false)
            ->assertSee('class="feature-hero', false)
            ->assertSee('What we do')
            ->assertSee('feature-hero-lattice', false)
            ->assertSee('href="'.route('contact').'"', false);
    }

    public function test_insights_uses_the_photo_hero_until_articles_are_published(): void
    {
        $this->get('/insights')
            ->assertOk()
            ->assertSee('class="feature-hero', false)
            ->assertSee('DXCOVR Insights')
            ->assertSee('Nothing published here yet');
    }

    public function test_hero_photo_label_and_button_are_editable_in_the_admin(): void
    {
        $photo = Media::factory()->create();
        $page = Page::system('services');
        $this->actingAsRole('Editor');

        $this->put(route('admin.pages.update', $page), [
            'title' => 'Services',
            'excerpt' => 'What we do.',
            'hero_media_id' => $photo->id,
            'hero_eyebrow' => 'Our expertise',
            'hero_cta_label' => 'Book a call',
            'hero_cta_url' => '/contact?type=project',
        ])->assertSessionHasNoErrors();

        $page->refresh();
        $this->assertSame($photo->id, $page->hero_media_id);

        $this->get('/services')
            ->assertSee('feature-hero-photo', false)
            ->assertDontSee('feature-hero-lattice', false)
            ->assertSee('Our expertise')
            ->assertSee('Book a call')
            ->assertSee('/contact?type=project', false);
    }

    public function test_hero_button_link_must_be_safe(): void
    {
        $this->actingAsRole('Editor');

        $this->put(route('admin.pages.update', Page::system('services')), [
            'title' => 'Services',
            'hero_cta_label' => 'Go',
            'hero_cta_url' => 'javascript:alert(1)',
        ])->assertSessionHasErrors('hero_cta_url');
    }

    public function test_other_system_pages_ignore_hero_fields(): void
    {
        $this->actingAsRole('Editor');
        $about = Page::system('about');

        $this->put(route('admin.pages.update', $about), ['title' => 'About', 'hero_eyebrow' => 'Ignored'])->assertSessionHasNoErrors();

        $this->assertNull($about->fresh()->hero_eyebrow);
    }

    public function test_clients_section_is_hidden_until_clients_are_added(): void
    {
        $this->get('/')->assertOk()->assertDontSee('class="clients-heading"', false);

        $logo = Media::factory()->create();
        $this->clients()->update(['data' => ['heading' => 'Clients', 'items' => [
            ['media_id' => $logo->id, 'name' => 'Accra Health Group', 'body' => 'Patient booking platform and staff dashboard', 'url' => '/work'],
            ['media_id' => null, 'name' => 'Volta Farms', 'body' => 'Sensor network for irrigation monitoring', 'url' => null],
        ]]]);

        $this->get('/')
            ->assertSee('class="clients-heading"', false)
            ->assertSee('alt="Accra Health Group"', false)
            ->assertSee('Patient booking platform and staff dashboard')
            ->assertSee('Volta Farms')
            ->assertSee('View the work');
    }

    public function test_client_entries_are_validated(): void
    {
        $this->actingAsRole('Editor');
        $section = $this->clients();

        $this->put(route('admin.sections.update', $section), ['sections' => [$section->id => ['is_enabled' => '1', 'data' => [
            'heading' => 'Clients',
            'items' => [['media_id' => '', 'name' => 'No description', 'body' => '', 'url' => 'javascript:alert(1)']],
        ]]]])->assertSessionHasErrors([
            "sections.{$section->id}.data.items.0.body",
            "sections.{$section->id}.data.items.0.url",
        ]);
    }
}
