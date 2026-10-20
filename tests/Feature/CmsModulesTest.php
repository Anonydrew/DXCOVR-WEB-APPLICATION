<?php

namespace Tests\Feature;

use App\Enums\PublicationStatus;
use App\Models\Announcement;
use App\Models\Faq;
use App\Models\Media;
use App\Models\NavigationMenu;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\Product;
use App\Models\Project;
use App\Models\Redirect;
use App\Models\Service;
use App\Models\TeamMember;
use App\Models\Testimonial;
use App\Services\SiteSettings;
use App\Support\Permissions;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CmsModulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_crud_publish_and_reorder(): void
    {
        $this->actingAsRole('Editor');

        $this->post('/admin/services', [
            'title' => 'Cloud Infrastructure',
            'icon' => 'support',
            'short_description' => 'Reliable hosting and deployment.',
            'body' => '<p>We look after servers.</p>',
            'capabilities' => ['Monitoring', '', 'Backups'],
            'use_cases' => [['title' => 'Migration', 'body' => 'Move to the cloud'], ['title' => '', 'body' => '']],
            'process_steps' => [['title' => 'Assess', 'body' => '']],
            'hero_video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'is_featured' => '1',
        ])->assertSessionHasNoErrors();

        $service = Service::query()->where('slug', 'cloud-infrastructure')->firstOrFail();
        $this->assertSame(['Monitoring', 'Backups'], $service->capabilities);
        $this->assertCount(1, $service->use_cases);
        $this->assertTrue($service->is_featured);

        $this->post(route('admin.publication', ['service', $service->id]), ['action' => 'publish']);
        $this->get(route('services.show', $service))->assertOk()->assertSee('Cloud Infrastructure')->assertSee('youtube-nocookie.com/embed/dQw4w9WgXcQ', false);

        $other = Service::factory()->create();
        $this->postJson(route('admin.reorder', 'services'), ['ids' => [$other->id, $service->id]])->assertOk();
        $this->assertSame(0, $other->fresh()->sort_order);
        $this->assertSame(1, $service->fresh()->sort_order);

        $this->put(route('admin.services.update', $service), ['title' => 'Cloud Infrastructure', 'icon' => 'support', 'short_description' => 'x', 'hero_video_url' => 'https://evil.example/video'])
            ->assertSessionHasErrors('hero_video_url');

        $this->delete(route('admin.services.destroy', $service))->assertRedirect(route('admin.services.index'));
        $this->assertModelMissing($service);
    }

    public function test_project_crud_with_relationships_and_gallery(): void
    {
        $this->actingAsRole('Editor');
        $service = Service::factory()->published()->create();
        $image = Media::factory()->create();

        $this->post('/admin/projects', [
            'title' => 'Clinic queue system',
            'summary' => 'A queue management system for a busy clinic.',
            'project_type' => 'client',
            'client_name' => 'Example Health',
            'is_confidential' => '1',
            'anonymised_client_label' => 'A private clinic in Accra',
            'challenge' => '<p>Long waits</p>',
            'solution' => '<p>Digital queues</p>',
            'services' => [$service->id],
            'technologies' => 'Laravel, MQTT, laravel',
            'features' => [['title' => 'SMS alerts', 'body' => 'Patients are notified']],
            'gallery' => [['media_id' => $image->id, 'caption' => 'Waiting room display']],
            'video_urls' => ['https://vimeo.com/123456789'],
        ])->assertSessionHasNoErrors();

        $project = Project::query()->firstOrFail();
        $this->assertSame([$service->id], $project->services->modelKeys());
        $this->assertSame(['Laravel', 'MQTT'], $project->technologies->pluck('name')->all());
        $this->assertSame('Waiting room display', $project->mediaCollection('gallery')->first()->caption);

        $this->post(route('admin.publication', ['project', $project->id]), ['action' => 'publish']);

        $this->get(route('work.show', $project))
            ->assertOk()
            ->assertSee('A private clinic in Accra')
            ->assertDontSee('Example Health')
            ->assertSee('Client project')
            ->assertSee('player.vimeo.com/video/123456789', false);

        $this->get('/work?technology=mqtt')->assertSee('Clinic queue system');
    }

    public function test_client_projects_need_a_client_or_confidentiality_before_publishing(): void
    {
        $this->actingAsRole('Editor');
        $project = Project::factory()->create(['project_type' => 'client', 'client_name' => null, 'is_confidential' => false]);

        $this->post(route('admin.publication', ['project', $project->id]), ['action' => 'publish'])->assertSessionHasErrors('publication');
    }

    public function test_product_crud_and_pricing_guard(): void
    {
        $this->actingAsRole('Editor');

        $this->post('/admin/products', [
            'name' => 'Field Ops',
            'short_description' => 'Mobile tools for field teams.',
            'product_status' => 'beta',
            'features' => [['title' => 'Offline mode', 'body' => '']],
            'modules' => [['title' => 'Scheduling', 'body' => 'Plan visits']],
            'integrations' => ['SMS'],
            'demo_requests_enabled' => '1',
            'enquiries_enabled' => '0',
            'pricing_visible' => '1',
            'pricing' => '',
        ])->assertSessionHasNoErrors();

        $product = Product::query()->where('slug', 'field-ops')->firstOrFail();

        $this->post(route('admin.publication', ['product', $product->id]), ['action' => 'publish'])->assertSessionHasErrors('publication');

        $product->update(['pricing_visible' => false]);
        $this->post(route('admin.publication', ['product', $product->id]), ['action' => 'publish'])->assertSessionHasNoErrors();

        $this->get(route('products.show', $product))->assertOk()->assertSee('Scheduling')->assertSee('Request a demo')->assertSee('Beta');
    }

    public function test_homepage_sections_are_validated_and_sanitised(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->actingAsRole('Editor');
        $hero = Page::system('home')->sections()->where('type', 'hero')->firstOrFail();

        $this->put(route('admin.sections.update', $hero), ['sections' => [$hero->id => ['data' => [
            'headline' => 'New headline',
            'primary_label' => 'Go',
            'primary_url' => 'javascript:alert(1)',
        ]]]])->assertSessionHasErrors("sections.{$hero->id}.data.primary_url");

        $this->put(route('admin.sections.update', $hero), ['sections' => [$hero->id => ['data' => ['headline' => '']]]])
            ->assertSessionHasErrors("sections.{$hero->id}.data.headline");

        $this->put(route('admin.sections.update', $hero), ['sections' => [$hero->id => [
            'is_enabled' => '1',
            'data' => [
                'headline' => 'Engineering that *ships*',
                'primary_label' => 'See work',
                'primary_url' => '/work',
                'unexpected_field' => '<script>alert(1)</script>',
                'disciplines' => [['label' => 'Robotics'], ['label' => '']],
            ],
        ]]])->assertSessionHasNoErrors();

        $hero->refresh();
        $this->assertSame('Engineering that *ships*', $hero->value('headline'));
        $this->assertArrayNotHasKey('unexpected_field', $hero->data);
        $this->assertCount(1, $hero->value('disciplines'));

        $this->get('/')->assertSee('Engineering that <span class="accent">ships</span>', false)->assertSee('Robotics');
    }

    public function test_rich_text_sections_strip_scripts(): void
    {
        $this->actingAsRole('Editor');
        $page = Page::factory()->create();
        $section = $page->sections()->create(['type' => 'rich_text', 'sort_order' => 0, 'is_enabled' => true, 'data' => []]);

        $this->put(route('admin.sections.update', $section), ['sections' => [$section->id => ['is_enabled' => '1', 'data' => [
            'heading' => 'About',
            'body' => '<p>Safe</p><script>alert(1)</script><img src=x onerror=alert(1)>',
        ]]]])->assertSessionHasNoErrors();

        $body = $section->fresh()->value('body');
        $this->assertStringNotContainsString('<script', $body);
        $this->assertStringNotContainsString('onerror', $body);
    }

    public function test_sections_can_be_added_toggled_and_removed(): void
    {
        $this->actingAsRole('Editor');
        $page = Page::factory()->create();

        $this->post(route('admin.pages.sections.store', $page), ['type' => 'not_a_section'])->assertSessionHasErrors('type');
        $this->post(route('admin.pages.sections.store', $page), ['type' => 'steps']);

        $section = PageSection::query()->firstOrFail();
        $this->assertFalse($section->is_enabled, 'New sections start hidden.');

        $this->post(route('admin.sections.toggle', $section));
        $this->assertTrue($section->fresh()->is_enabled);

        $this->delete(route('admin.sections.destroy', $section));
        $this->assertModelMissing($section);
    }

    public function test_custom_pages_cannot_use_reserved_slugs_and_system_pages_cannot_be_deleted(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->actingAsRole('Editor');

        $this->post('/admin/pages', ['title' => 'Admin', 'slug' => 'admin', 'template' => 'standard'])->assertSessionHasErrors('slug');
        $this->post('/admin/pages', ['title' => 'Services', 'slug' => 'services', 'template' => 'standard'])->assertSessionHasErrors('slug');

        $this->delete(route('admin.pages.destroy', Page::system('home')))->assertForbidden();
        $this->post(route('admin.publication', ['page', Page::system('home')->id]), ['action' => 'unpublish'])->assertSessionHas('error');
    }

    public function test_navigation_items_can_be_managed_and_unsafe_links_rejected(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->actingAsRole('Editor');
        $menu = NavigationMenu::query()->where('key', 'primary')->firstOrFail();

        $this->post(route('admin.navigation.items.store', $menu), ['label' => 'Bad', 'url' => 'javascript:alert(1)'])->assertSessionHasErrors('url');
        $this->post(route('admin.navigation.items.store', $menu), ['label' => 'Labs', 'url' => '/labs', 'is_enabled' => '1'])->assertSessionHasNoErrors();

        $this->get('/')->assertSee('>Labs</a>', false);

        $item = $menu->items()->where('label', 'Labs')->firstOrFail();
        $this->put(route('admin.navigation.items.update', $item), ['label' => 'Labs', 'url' => '/labs', 'is_enabled' => '0']);
        $this->get('/')->assertDontSee('>Labs</a>', false);
    }

    public function test_settings_are_saved_and_validated(): void
    {
        $this->actingAsRole('Administrator');

        $this->put(route('admin.settings.update', 'contact'), ['settings' => [
            'contact-email' => 'hello@dxcovr.test',
            'contact-whatsapp_number' => '+233 20 000 0000',
            'social-links' => [['network' => 'linkedin', 'url' => 'https://www.linkedin.com/company/example', 'label' => '']],
        ]])->assertSessionHasNoErrors();

        $settings = app(SiteSettings::class);
        $settings->flush();
        $this->assertSame('hello@dxcovr.test', $settings->get('contact.email'));
        $this->assertStringStartsWith('https://wa.me/233200000000', $settings->whatsappUrl());

        $this->get('/contact')->assertSee('hello@dxcovr.test')->assertSee('wa.me/233200000000', false);

        $this->put(route('admin.settings.update', 'notifications'), ['settings' => ['notifications-enquiry_recipients' => "ok@dxcovr.test\nnot-an-email"]])
            ->assertSessionHasErrors('settings.notifications-enquiry_recipients');

        $this->put(route('admin.settings.update', 'seo'), ['settings' => ['seo-title_template' => 'No placeholder']])
            ->assertSessionHasErrors('settings.seo-title_template');
    }

    public function test_settings_groups_require_their_own_permission(): void
    {
        $this->actingAsRole('Editor');

        $this->get(route('admin.settings.edit', 'seo'))->assertOk();
        $this->get(route('admin.settings.edit', 'general'))->assertForbidden();
        $this->get(route('admin.settings.edit', 'nonsense'))->assertNotFound();
    }

    public function test_redirect_management(): void
    {
        $this->actingAsRole('Editor');

        $this->post('/admin/redirects', ['from_path' => '/admin/login', 'to_url' => '/', 'status_code' => 301])->assertSessionHasErrors('from_path');
        $this->post('/admin/redirects', ['from_path' => '/loop', 'to_url' => '/loop', 'status_code' => 301])->assertSessionHasErrors('to_url');
        $this->post('/admin/redirects', ['from_path' => 'old-blog/', 'to_url' => '/insights', 'status_code' => 301, 'is_active' => '1'])->assertSessionHasNoErrors();

        $this->assertSame('/old-blog', Redirect::query()->first()->from_path);
        $this->get('/old-blog')->assertRedirect(url('/insights'));
    }

    public function test_founder_profile_is_editable_and_published(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->actingAsRole('Editor');

        $this->put(route('admin.founder.update'), [
            'name' => 'Anthony Jnr Ahiable',
            'role_title' => 'Founder & Principal Engineer',
            'short_bio' => 'Leads DXCOVR.',
            'expertise' => ['Systems thinking'],
            'skills' => ['Laravel', 'Arduino'],
            'experience' => [['role' => 'Founder', 'organisation' => 'DXCOVR', 'period' => '', 'summary' => '']],
            'links' => [['label' => 'LinkedIn', 'url' => 'https://www.linkedin.com/in/example']],
            'is_published' => '1',
        ])->assertSessionHasNoErrors();

        $this->assertSame(1, TeamMember::query()->where('is_founder', true)->count());
        $this->get('/about/founder')->assertOk()->assertSee('Founder &amp; Principal Engineer', false)->assertSee('Arduino');
    }

    public function test_testimonials_need_consent_to_be_published(): void
    {
        $this->actingAsRole('Editor');

        $this->post('/admin/testimonials', ['quote' => 'Great work.', 'author_name' => 'A. Client', 'consent_confirmed' => '0', 'is_published' => '1'])
            ->assertSessionHasErrors('is_published');

        $this->post('/admin/testimonials', ['quote' => 'Great work.', 'author_name' => 'A. Client', 'consent_confirmed' => '1', 'is_published' => '1'])
            ->assertSessionHasNoErrors();

        $this->assertSame(1, Testimonial::query()->published()->count());
    }

    public function test_faqs_and_announcements(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->actingAsRole('Editor');
        $service = Service::query()->first();

        $this->post('/admin/faqs', ['question' => 'Do you offer support?', 'answer' => '<p>Yes.</p><script>x</script>', 'group' => 'projects', 'service_id' => $service->id, 'is_published' => '1'])
            ->assertSessionHasNoErrors();
        $this->assertStringNotContainsString('script', Faq::query()->latest('id')->first()->answer);
        $this->get(route('services.show', $service))->assertSee('Do you offer support?');

        $this->post('/admin/announcements', ['title' => 'We are hiring', 'link_url' => '/careers', 'is_active' => '1'])->assertSessionHasNoErrors();
        $this->get('/')->assertSee('We are hiring');

        Announcement::query()->first()->update(['ends_at' => now()->subMinute()]);
        $this->get('/')->assertDontSee('We are hiring');
    }

    public function test_reordering_requires_the_module_permission(): void
    {
        $this->actingAsRole('Author');
        $service = Service::factory()->create();

        $this->postJson(route('admin.reorder', 'services'), ['ids' => [$service->id]])->assertForbidden();
        $this->postJson(route('admin.reorder', 'unknown'), ['ids' => [1]])->assertNotFound();
    }

    public function test_taxonomy_management(): void
    {
        $this->actingAsRole('Editor');

        $this->post(route('admin.article-categories.store'), ['name' => 'Robotics', 'description' => 'Robots'])->assertSessionHasNoErrors();
        $this->post(route('admin.article-categories.store'), ['name' => 'Robotics'])->assertSessionHasErrors('name');
        $this->assertDatabaseHas('article_categories', ['slug' => 'robotics']);

        $this->post(route('admin.technologies.store'), ['name' => 'Unity'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('technologies', ['slug' => 'unity']);
    }

    public function test_publication_permissions_are_enforced_per_content_type(): void
    {
        $this->actingAsRole('Careers Manager');
        $service = Service::factory()->create();

        $this->post(route('admin.publication', ['service', $service->id]), ['action' => 'publish'])->assertForbidden();
        $this->post(route('admin.publication', ['nonsense', 1]), ['action' => 'publish'])->assertNotFound();
        $this->assertSame(PublicationStatus::Draft, $service->fresh()->status);
    }

    public function test_super_admin_role_bypasses_but_role_list_is_protected(): void
    {
        $this->actingAsRole(Permissions::SUPER_ADMIN_ROLE);
        $role = Role::findByName(Permissions::SUPER_ADMIN_ROLE);

        $this->get(route('admin.roles.edit', $role))->assertRedirect(route('admin.roles.index'));
        $this->delete(route('admin.roles.destroy', $role))->assertForbidden();
    }
}
