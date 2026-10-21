<?php

namespace Tests\Feature;

use App\Enums\MediaKind;
use App\Models\Media;
use App\Services\SiteSettings;
use App\Support\SocialNetworks;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SocialProfilesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->syncPermissions();
    }

    public function test_profiles_show_brand_logos_and_custom_icons(): void
    {
        $this->actingAsRole('Administrator');
        $icon = Media::factory()->create();

        $this->put(route('admin.settings.update', 'contact'), ['settings' => ['social-links' => [
            ['network' => 'tiktok', 'url' => 'https://www.tiktok.com/@dxcovr', 'label' => '', 'icon' => ''],
            ['network' => 'link', 'url' => 'https://example.com/community', 'label' => 'Community', 'icon' => (string) $icon->id],
            ['network' => 'linkedin', 'url' => '', 'label' => '', 'icon' => ''],
        ]]])->assertSessionHasNoErrors();

        $settings = app(SiteSettings::class);
        $settings->flush();
        $this->assertEquals([
            ['network' => 'tiktok', 'url' => 'https://www.tiktok.com/@dxcovr', 'label' => null, 'icon' => null],
            ['network' => 'link', 'url' => 'https://example.com/community', 'label' => 'Community', 'icon' => $icon->id],
        ], $settings->get('social.links'));

        $this->get('/')
            ->assertOk()
            ->assertSee('aria-label="TikTok"', false)
            ->assertSee(SocialNetworks::path('tiktok'), false)
            ->assertSee('aria-label="Community"', false)
            ->assertSee('class="social-custom-icon"', false);

        $this->get('/contact')->assertOk()->assertSee('TikTok')->assertSee('Community');
        $this->get(route('admin.settings.edit', 'contact'))->assertOk()->assertSee('Custom icon (optional)')->assertSee('Snapchat');
    }

    public function test_icons_must_be_images_and_networks_must_be_known(): void
    {
        $this->actingAsRole('Administrator');
        $document = Media::factory()->create(['kind' => MediaKind::Document, 'mime_type' => 'application/pdf']);

        $this->put(route('admin.settings.update', 'contact'), ['settings' => ['social-links' => [
            ['network' => 'myspace', 'url' => 'https://example.com', 'label' => '', 'icon' => (string) $document->id],
        ]]])->assertSessionHasErrors(['settings.social-links.0.network', 'settings.social-links.0.icon']);
    }

    public function test_an_icon_in_use_cannot_be_deleted(): void
    {
        $this->actingAsRole('Administrator');
        $icon = Media::factory()->create();
        app(SiteSettings::class)->set(['social.links' => [['network' => 'link', 'url' => 'https://example.com', 'label' => 'Forum', 'icon' => $icon->id]]]);

        $this->get(route('admin.media.show', $icon))->assertOk()->assertSee('Social icon: Forum');
        $this->delete(route('admin.media.destroy', $icon))->assertSessionHas('error');
        $this->assertModelExists($icon);
    }

    public function test_every_network_has_a_logo(): void
    {
        foreach (array_keys(SocialNetworks::NETWORKS) as $network) {
            $this->assertMatchesRegularExpression('/^M[\d.\-\s,A-Za-z]+$/', (string) SocialNetworks::path($network), $network);
        }

        $this->assertSame('Other', SocialNetworks::options()['link']);
        $this->assertNull(SocialNetworks::path('link'));
    }
}
