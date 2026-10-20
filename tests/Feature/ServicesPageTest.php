<?php

namespace Tests\Feature;

use App\Enums\MediaKind;
use App\Models\Media;
use App\Models\Service;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServicesPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    protected function video(): Media
    {
        return Media::factory()->create([
            'kind' => MediaKind::Video,
            'mime_type' => 'video/mp4',
            'extension' => 'mp4',
            'path' => 'media/2026/10/loop.mp4',
            'width' => null,
            'height' => null,
        ]);
    }

    public function test_each_service_shows_title_summary_list_and_media(): void
    {
        $service = Service::query()->where('slug', 'software-engineering-and-business-systems')->firstOrFail();

        $this->get('/services')
            ->assertOk()
            ->assertSee('class="service-feature"', false)
            ->assertSee('id="'.$service->slug.'"', false)
            ->assertSee($service->short_description)
            ->assertSee('<li>Custom applications</li>', false)
            ->assertSee('<li>Workflow engineering</li>', false)
            ->assertSee('Explore the service');

        $this->assertSame(Service::query()->published()->count(), substr_count($this->get('/services')->getContent(), 'class="service-feature"'));
    }

    public function test_looping_video_plays_muted_with_the_hero_image_as_poster(): void
    {
        $service = Service::query()->firstOrFail();
        $poster = Media::factory()->create();
        $service->update(['loop_video_media_id' => $this->video()->id, 'hero_media_id' => $poster->id]);

        $this->get('/services')
            ->assertSee('data-loop-video', false)
            ->assertSee('muted loop playsinline', false)
            ->assertSee('poster="'.$poster->variantUrl(1600).'"', false)
            ->assertSee('type="video/mp4"', false)
            ->assertSee('data-video-toggle', false);
    }

    public function test_loop_video_must_be_a_video_from_the_media_library(): void
    {
        $this->actingAsRole('Editor');
        $service = Service::query()->firstOrFail();
        $image = Media::factory()->create();
        $base = ['title' => $service->title, 'icon' => $service->icon, 'short_description' => $service->short_description];

        $this->put(route('admin.services.update', $service), $base + ['loop_video_media_id' => $image->id])
            ->assertSessionHasErrors('loop_video_media_id');

        $video = $this->video();
        $this->put(route('admin.services.update', $service), $base + ['loop_video_media_id' => $video->id])
            ->assertSessionDoesntHaveErrors('loop_video_media_id');

        $this->assertSame($video->id, $service->fresh()->loop_video_media_id);
    }
}
