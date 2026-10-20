<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\Page;
use App\Models\PageSection;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HeroSectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    protected function hero(): PageSection
    {
        return Page::system('home')->sections()->where('type', 'hero')->firstOrFail();
    }

    /**
     * @param  array<string, mixed>  $changes
     */
    protected function updateHero(array $changes): void
    {
        $hero = $this->hero();
        $hero->update(['data' => array_merge($hero->data, $changes)]);
    }

    public function test_hero_renders_proof_strip_cards_and_transparent_header(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('class="min-h-dvh flex flex-col has-overlay-header"', false)
            ->assertSee('Inside DXCOVR')
            ->assertSee('Service disciplines')
            ->assertSee('href="'.url('/services').'"', false)
            ->assertSee('class="hero-cards" aria-hidden="true"', false)
            ->assertSee('Software engineering');
    }

    public function test_proof_strip_scrolls_with_an_inert_duplicate_and_a_pause_control(): void
    {
        $html = $this->get('/')
            ->assertSee('DXCOVR Clinic ERP')
            ->assertSee('How we work')
            ->assertSee('hero-strip-viewport mt-8 is-animated', false)
            ->assertSee('data-motion-toggle', false)
            ->assertSee('Pause scrolling')
            ->assertSee('<ul class="hero-strip-group" aria-hidden="true" inert>', false)
            ->getContent();

        $this->assertSame(2, substr_count($html, 'aria-label="Learn more: Our services"'), 'Each card renders twice for the loop.');
    }

    public function test_fewer_than_three_cards_stay_static(): void
    {
        $this->updateHero(['proof_items' => [
            ['value' => '7', 'label' => 'Disciplines', 'title' => 'Our services', 'media_id' => null, 'body' => 'One roof.', 'url' => '/services'],
        ]]);

        $this->get('/')
            ->assertSee('hero-strip-viewport mt-8 is-static', false)
            ->assertDontSee('<ul class="hero-strip-group" aria-hidden="true" inert>', false);
    }

    public function test_card_logo_replaces_the_source_line(): void
    {
        $logo = Media::factory()->create(['alt' => 'Product logo']);
        $this->updateHero(['proof_items' => [
            ['value' => 'ERP', 'label' => 'Product', 'title' => 'Clinic ERP', 'media_id' => $logo->id, 'body' => 'One system.', 'url' => '/products'],
        ]]);

        $this->get('/')
            ->assertSee('class="hero-proof-logo"', false)
            ->assertSee('alt="Clinic ERP"', false)
            ->assertDontSee('<p class="hero-proof-title">', false);
    }

    public function test_other_pages_keep_the_solid_header(): void
    {
        $this->get('/about')->assertOk()->assertDontSee('has-overlay-header');
        $this->get('/contact')->assertOk()->assertDontSee('has-overlay-header');
    }

    public function test_background_photo_replaces_the_artwork(): void
    {
        $photo = Media::factory()->create();
        $this->updateHero(['media_id' => $photo->id]);

        $this->get('/')
            ->assertSee('hero-photo', false)
            ->assertDontSee('hero-artwork-visual', false)
            ->assertSee('fetchpriority="high"', false);
    }

    public function test_interface_cards_can_be_switched_off(): void
    {
        $this->updateHero(['show_ui_cards' => false]);

        $this->get('/')->assertDontSee('class="hero-cards"', false);
    }

    public function test_logos_replace_disciplines_only_when_images_are_provided(): void
    {
        $this->updateHero(['logos' => [['media_id' => null, 'name' => 'No image yet']]]);
        $this->get('/')->assertDontSee('No image yet')->assertSee('Software engineering');

        $logo = Media::factory()->create();
        $this->updateHero(['logos' => [['media_id' => $logo->id, 'name' => 'Partner Org']]]);

        $this->get('/')->assertSee('Partner Org')->assertSee('class="hero-logo"', false);
    }

    public function test_demo_content_adds_placeholder_logos_without_replacing_real_ones(): void
    {
        Storage::fake('public');

        $this->seed(DemoContentSeeder::class);

        $logos = $this->hero()->data['logos'];
        $this->assertCount(6, $logos);
        $this->assertSame('Sample logo 1', $logos[0]['name']);
        $this->get('/')->assertSee('class="hero-logo"', false)->assertSee('Sample logo 1');

        $real = Media::factory()->create();
        $this->updateHero(['logos' => [['media_id' => $real->id, 'name' => 'Real Partner']]]);
        $this->seed(DemoContentSeeder::class);

        $this->assertSame('Real Partner', $this->hero()->data['logos'][0]['name']);
    }

    public function test_hero_fields_are_validated_in_the_admin(): void
    {
        $this->actingAsRole('Editor');
        $hero = $this->hero();

        $this->put(route('admin.sections.update', $hero), ['sections' => [$hero->id => ['is_enabled' => '1', 'data' => [
            'headline' => 'New headline',
            'proof_items' => [['value' => 'Way too long value', 'label' => '', 'body' => '', 'url' => 'javascript:alert(1)']],
            'logos' => [['media_id' => '', 'name' => '']],
        ]]]])->assertSessionHasErrors([
            "sections.{$hero->id}.data.proof_items.0.value",
            "sections.{$hero->id}.data.proof_items.0.url",
        ]);

        $this->put(route('admin.sections.update', $hero), ['sections' => [$hero->id => ['is_enabled' => '1', 'data' => [
            'headline' => 'New headline',
            'show_ui_cards' => '0',
            'proof_items' => [['value' => '24/7', 'label' => 'Support', 'body' => 'Always on.', 'url' => '/contact']],
            'logos' => [['media_id' => '', 'name' => '']],
        ]]]])->assertSessionHasNoErrors();

        $data = $hero->fresh()->data;
        $this->assertFalse($data['show_ui_cards']);
        $this->assertSame([], $data['logos'], 'Blank logo rows are ignored.');
        $this->assertSame('24/7', $data['proof_items'][0]['value']);
    }
}
