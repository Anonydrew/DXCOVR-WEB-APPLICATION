<?php

namespace Tests\Feature;

use App\Enums\ProjectType;
use App\Models\Project;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkGalleryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_homepage_gallery_shows_title_and_one_short_line_per_project(): void
    {
        Project::factory()->published()->create([
            'title' => 'Clinic booking app',
            'tagline' => 'Booking app for a family clinic',
            'summary' => 'A long summary that should not appear on the homepage tile. It goes on and on.',
            'is_featured' => true,
            'sort_order' => 1,
        ]);
        Project::factory()->published()->create([
            'title' => 'Sensor network',
            'tagline' => null,
            'summary' => 'Soil sensors for smallholder farms. Built with LoRa radios and solar power.',
            'is_featured' => true,
            'sort_order' => 2,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('class="surface-light work-gallery"', false)
            ->assertSee('Selected work')
            ->assertSee('Clinic booking app')
            ->assertSee('Booking app for a family clinic')
            ->assertDontSee('A long summary that should not appear')
            ->assertSee('Soil sensors for smallholder farms')
            ->assertDontSee('Built with LoRa radios')
            ->assertDontSee('View case study')
            ->assertSee('--span: 14', false)
            ->assertSee('--span: 10', false);
    }

    public function test_short_line_falls_back_to_a_trimmed_first_sentence(): void
    {
        $project = new Project(['summary' => str_repeat('word ', 40)]);
        $this->assertLessThanOrEqual(93, mb_strlen($project->shortLine()));

        $project = new Project(['summary' => 'Patient records in one place. Then more.', 'tagline' => '']);
        $this->assertSame('Patient records in one place', $project->shortLine());
    }

    public function test_tagline_is_saved_and_limited_in_the_admin(): void
    {
        $this->actingAsRole('Editor');
        $base = ['title' => 'Clinic app', 'summary' => 'Summary.', 'project_type' => ProjectType::values()[0]];

        $this->post(route('admin.projects.store'), $base + ['tagline' => str_repeat('x', 91)])
            ->assertSessionHasErrors('tagline');

        $this->post(route('admin.projects.store'), $base + ['tagline' => 'Booking app for a family clinic'])
            ->assertSessionHasNoErrors();

        $this->assertSame('Booking app for a family clinic', Project::query()->where('title', 'Clinic app')->sole()->tagline);
    }
}
