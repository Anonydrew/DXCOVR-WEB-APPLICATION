<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Media;
use App\Models\Page;
use App\Models\PageSection;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AboutPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    protected function section(string $type): PageSection
    {
        return Page::system('about')->sections()->where('type', $type)->firstOrFail();
    }

    public function test_about_page_follows_the_company_page_layout(): void
    {
        Article::factory()->published()->create(['title' => 'Our latest company update']);

        $this->get('/about')
            ->assertOk()
            ->assertSee('class="about-hero"', false)
            ->assertSee('<h1 id="about-heading" class="about-hero-title mt-6"', false)
            ->assertSee('Product of our own')
            ->assertSee('class="about-stat-value">7</p>', false)
            ->assertSee('What we do')
            ->assertSee('data-carousel-dot', false)
            ->assertSee('Explore this service')
            ->assertSee('Our mission')
            ->assertSee('Get to know DXCOVR')
            ->assertSee('Meet the founder')
            ->assertSee('Latest updates')
            ->assertSee('story-card-light', false)
            ->assertSee('Our latest company update');
    }

    public function test_history_stays_hidden_until_milestones_are_added(): void
    {
        $this->get('/about')->assertDontSee('timeline-card', false);

        $photo = Media::factory()->create();
        $section = $this->section('milestones');
        $section->update(['data' => array_merge($section->data, ['eyebrow' => '2024 to today', 'items' => [
            ['period' => '2024', 'title' => 'DXCOVR was founded', 'body' => null, 'media_id' => $photo->id],
            ['period' => '2025', 'title' => 'First client system launched', 'body' => 'A booking platform.', 'media_id' => null],
        ]])]);

        $this->get('/about')
            ->assertSee('class="timeline-card"', false)
            ->assertSee('DXCOVR was founded')
            ->assertSee('2024 to today')
            ->assertSee('timeline-card-media', false);
    }

    public function test_mission_band_shows_optional_banner_and_photos(): void
    {
        $banner = Media::factory()->create();
        $photo = Media::factory()->create();
        $section = $this->section('mission');
        $section->update(['data' => array_merge($section->data, ['media_id' => $banner->id, 'photo_one_media_id' => $photo->id])]);

        $this->get('/about')->assertSee('class="mission-banner"', false)->assertSee('aspect-[21/9]', false);
    }

    public function test_new_section_fields_are_validated(): void
    {
        $this->actingAsRole('Editor');
        $cards = $this->section('link_cards');

        $this->put(route('admin.sections.update', $cards), ['sections' => [$cards->id => ['is_enabled' => '1', 'data' => [
            'heading' => 'Get to know us',
            'items' => [['label' => 'Bad link', 'button_label' => 'Go', 'url' => 'javascript:alert(1)', 'media_id' => '', 'glyph' => 'software']],
        ]]]])->assertSessionHasErrors("sections.{$cards->id}.data.items.0.url");

        $overview = $this->section('about_overview');

        $this->put(route('admin.sections.update', $overview), ['sections' => [$overview->id => ['is_enabled' => '1', 'data' => [
            'lead' => 'Lead', 'body' => 'Body',
            'stats' => [['value' => 'Far too long a figure', 'label' => 'Label', 'note' => '']],
        ]]]])->assertSessionHasErrors("sections.{$overview->id}.data.stats.0.value");
    }
}
