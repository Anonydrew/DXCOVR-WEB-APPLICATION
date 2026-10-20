<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\Page;
use App\Models\PageSection;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpotlightSectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    protected function spotlight(): PageSection
    {
        return Page::system('home')->sections()->where('type', 'spotlight')->firstOrFail();
    }

    public function test_homepage_shows_the_spotlight_carousel_and_quick_links(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('aria-roledescription="carousel"', false)
            ->assertSee('aria-roledescription="slide"', false)
            ->assertSee('DXCOVR Clinic ERP')
            ->assertSee('href="'.url('/products/dxcovr-clinic-erp').'"', false)
            ->assertSee('data-carousel-next', false)
            ->assertSee('Explore services')
            ->assertSee('Join the team');
    }

    public function test_cards_can_use_images_from_the_media_library(): void
    {
        $this->actingAsRole('Editor');
        $section = $this->spotlight();
        $image = Media::factory()->create(['alt' => 'Clinic reception dashboard on a tablet']);

        $this->put(route('admin.sections.update', $section), ['sections' => [$section->id => [
            'is_enabled' => '1',
            'data' => ['items' => [
                ['title' => 'Clinic ERP', 'badge' => 'New', 'body' => 'One system.', 'media_id' => $image->id, 'icon' => 'software', 'tone' => 'dark', 'cta_label' => 'Learn more', 'cta_url' => '/products'],
                ['title' => '', 'badge' => '', 'body' => '', 'media_id' => '', 'icon' => 'ai', 'tone' => 'lime', 'cta_label' => '', 'cta_url' => ''],
            ]],
        ]]])->assertSessionHasNoErrors();

        $items = $section->fresh()->value('items');
        $this->assertCount(1, $items, 'Blank cards (only default dropdown values) are ignored.');
        $this->assertSame($image->id, $items[0]['media_id']);

        $this->get('/')->assertSee('alt="Clinic reception dashboard on a tablet"', false)->assertSee('spotlight-photo', false);

        $this->delete(route('admin.media.destroy', $image))->assertSessionHas('error');
        $this->assertModelExists($image);
    }

    public function test_card_fields_are_validated(): void
    {
        $this->actingAsRole('Editor');
        $section = $this->spotlight();

        $this->put(route('admin.sections.update', $section), ['sections' => [$section->id => ['data' => ['items' => [
            ['title' => 'Bad link', 'media_id' => 999999, 'icon' => 'software', 'tone' => 'neon', 'cta_label' => 'Go', 'cta_url' => 'javascript:alert(1)'],
        ]]]]])->assertSessionHasErrors([
            "sections.{$section->id}.data.items.0.media_id",
            "sections.{$section->id}.data.items.0.tone",
            "sections.{$section->id}.data.items.0.cta_url",
        ]);
    }

    public function test_quick_links_require_safe_destinations(): void
    {
        $this->actingAsRole('Editor');
        $section = Page::system('home')->sections()->where('type', 'quick_links')->firstOrFail();

        $this->put(route('admin.sections.update', $section), ['sections' => [$section->id => ['data' => ['items' => [
            ['icon' => 'mail', 'label' => 'Email', 'url' => 'javascript:alert(1)'],
        ]]]]])->assertSessionHasErrors("sections.{$section->id}.data.items.0.url");
    }

    public function test_empty_carousel_is_hidden(): void
    {
        $this->spotlight()->update(['data' => ['items' => []]]);

        $this->get('/')->assertOk()->assertDontSee('aria-roledescription="carousel"', false);
    }

    public function test_section_editor_offers_an_image_picker_inside_cards(): void
    {
        $this->actingAsRole('Editor');

        $this->get(route('admin.pages.edit', Page::system('home')))
            ->assertOk()
            ->assertSee('Spotlight carousel')
            ->assertSee("chooseMedia(row, 'media_id')", false);
    }
}
