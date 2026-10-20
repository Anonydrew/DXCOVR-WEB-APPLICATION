<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectCategory;
use App\Models\Technology;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_work_page_lists_projects_with_title_short_line_and_type_label(): void
    {
        $category = ProjectCategory::query()->firstOrFail();
        Project::factory()->forClient()->published()->create([
            'title' => 'Clinic booking platform',
            'tagline' => 'Online bookings for a family clinic',
            'project_category_id' => $category->id,
        ]);

        $response = $this->get('/work');
        $this->assertMatchesRegularExpression('/class="work-pill"\s+aria-current="true"\s*>All</', $response->getContent());

        $response
            ->assertOk()
            ->assertSee('Work that solves real problems')
            ->assertSee('class="work-page-kicker" aria-hidden="true">work</p>', false)
            ->assertSee($category->name)
            ->assertSee('All services')
            ->assertSee('<h3 class="work-card-title">Clinic booking platform</h3>', false)
            ->assertSee('Online bookings for a family clinic')
            ->assertSee('class="work-card-badge">Client work</span>', false);
    }

    public function test_active_technology_filter_shows_as_a_removable_pill(): void
    {
        $technology = Technology::query()->create(['name' => 'Laravel', 'slug' => 'laravel']);
        $project = Project::factory()->published()->create(['title' => 'Built with Laravel']);
        $project->technologies()->attach($technology);
        Project::factory()->published()->create(['title' => 'Something else']);

        $this->get('/work?technology=laravel')
            ->assertOk()
            ->assertSee('Built with Laravel')
            ->assertDontSee('Something else')
            ->assertSee('aria-label="Remove filter: Laravel"', false)
            ->assertSee('<input type="hidden" name="technology" value="laravel">', false);
    }
}
