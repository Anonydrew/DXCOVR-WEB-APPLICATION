<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Enquiry;
use App\Models\JobApplication;
use App\Models\JobListing;
use App\Models\Media;
use App\Models\NavigationMenu;
use App\Models\Page;
use App\Models\Product;
use App\Models\Project;
use App\Models\Service;
use App\Models\User;
use App\Support\Permissions;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/admin')->assertRedirect(route('login'));
        $this->get('/admin/articles')->assertRedirect(route('login'));
        $this->get('/admin/applications')->assertRedirect(route('login'));
        $this->post('/admin/articles', ['title' => 'x'])->assertRedirect(route('login'));
    }

    public function test_there_is_no_public_registration_route(): void
    {
        $this->assertFalse(Route::has('register'));
        $this->get('/admin/register')->assertNotFound();
        $this->get('/register')->assertNotFound();
    }

    public function test_active_user_can_sign_in(): void
    {
        $user = User::factory()->withRole('Editor')->create(['email' => 'editor@example.com']);

        $this->post('/admin/login', ['email' => 'editor@example.com', 'password' => 'password'])
            ->assertRedirect('/admin');

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->last_login_at);
        $this->assertDatabaseHas('audit_logs', ['action' => 'auth.login', 'user_id' => $user->id]);
    }

    public function test_inactive_user_cannot_sign_in_and_gets_generic_error(): void
    {
        User::factory()->inactive()->create(['email' => 'gone@example.com']);

        $this->post('/admin/login', ['email' => 'gone@example.com', 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_deactivated_user_is_signed_out_on_next_request(): void
    {
        $user = $this->actingAsRole('Editor');
        $user->forceFill(['is_active' => false])->save();

        $this->get('/admin')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_login_is_rate_limited(): void
    {
        User::factory()->create(['email' => 'target@example.com']);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/admin/login', ['email' => 'target@example.com', 'password' => 'wrong-password']);
        }

        $this->post('/admin/login', ['email' => 'target@example.com', 'password' => 'wrong-password'])
            ->assertStatus(429);
    }

    public function test_admin_responses_are_not_indexable(): void
    {
        $this->actingAsRole(Permissions::SUPER_ADMIN_ROLE);

        $this->get('/admin')->assertOk()->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive');
    }

    public function test_super_admin_can_open_every_admin_screen(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->actingAsRole(Permissions::SUPER_ADMIN_ROLE);

        $article = Article::factory()->create();
        $service = Service::query()->first();
        $project = Project::factory()->create();
        $product = Product::query()->first();
        $job = JobListing::factory()->create();
        $application = JobApplication::factory()->create(['job_listing_id' => $job->id]);
        $enquiry = Enquiry::factory()->create();
        $media = Media::factory()->create();
        $page = Page::system('home');
        $menu = NavigationMenu::query()->where('key', 'primary')->first();
        $user = User::factory()->withRole('Editor')->create();
        $role = Role::query()->where('name', 'Editor')->first();

        $pages = [
            '/admin', '/admin/account', '/admin/audit-log',
            '/admin/articles', '/admin/articles/create', route('admin.articles.edit', $article),
            '/admin/article-categories', '/admin/tags',
            '/admin/services', '/admin/services/create', route('admin.services.edit', $service),
            '/admin/projects', '/admin/projects/create', route('admin.projects.edit', $project),
            '/admin/project-categories', '/admin/technologies',
            '/admin/products', '/admin/products/create', route('admin.products.edit', $product),
            '/admin/founder', '/admin/team', '/admin/team/create',
            '/admin/testimonials', '/admin/testimonials/create',
            '/admin/faqs', '/admin/faqs/create',
            '/admin/announcements', '/admin/announcements/create',
            '/admin/jobs', '/admin/jobs/create', route('admin.jobs.edit', $job), '/admin/departments',
            '/admin/applications', route('admin.applications.show', $application),
            '/admin/enquiries', route('admin.enquiries.show', $enquiry),
            '/admin/newsletter',
            '/admin/media', route('admin.media.show', $media), '/admin/media/picker',
            '/admin/pages', '/admin/pages/create', route('admin.pages.edit', $page),
            '/admin/navigation', route('admin.navigation.edit', $menu),
            '/admin/settings/general', '/admin/settings/contact', '/admin/settings/notifications', '/admin/settings/seo', '/admin/settings/careers', '/admin/settings/newsletter',
            '/admin/redirects', '/admin/redirects/create',
            '/admin/users', '/admin/users/create', route('admin.users.edit', $user),
            '/admin/roles', '/admin/roles/create', route('admin.roles.edit', $role),
        ];

        foreach ($pages as $path) {
            $this->get($path)->assertOk();
        }
    }

    public function test_read_only_analyst_can_view_but_not_change_content(): void
    {
        $this->actingAsRole('Read-only Analyst');
        $service = Service::factory()->create();

        $this->get('/admin')->assertOk();
        $this->get('/admin/services')->assertOk();
        $this->get('/admin/articles')->assertOk();

        $this->get('/admin/services/create')->assertForbidden();
        $this->get(route('admin.services.edit', $service))->assertForbidden();
        $this->put(route('admin.services.update', $service), ['title' => 'Changed'])->assertForbidden();
        $this->delete(route('admin.services.destroy', $service))->assertForbidden();
        $this->post('/admin/articles', ['title' => 'Nope'])->assertForbidden();
        $this->get('/admin/users')->assertForbidden();
        $this->get('/admin/settings/general')->assertForbidden();
        $this->get('/admin/applications')->assertForbidden();

        $this->assertNotSame('Changed', $service->fresh()->title);
    }

    public function test_careers_manager_cannot_reach_content_or_users(): void
    {
        $this->actingAsRole('Careers Manager');

        $this->get('/admin/jobs')->assertOk();
        $this->get('/admin/applications')->assertOk();
        $this->get('/admin/articles')->assertForbidden();
        $this->get('/admin/services')->assertForbidden();
        $this->get('/admin/users')->assertForbidden();
        $this->get('/admin/settings/careers')->assertOk();
        $this->get('/admin/settings/general')->assertForbidden();
    }

    public function test_navigation_only_shows_permitted_modules(): void
    {
        $this->actingAsRole('Author');

        $this->get('/admin')
            ->assertOk()
            ->assertSee('Articles')
            ->assertDontSee('Applications')
            ->assertDontSee('Roles &amp; permissions', false);
    }

    public function test_user_without_dashboard_permission_is_forbidden(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/admin')->assertForbidden();
    }
}
