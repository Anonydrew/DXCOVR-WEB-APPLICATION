<?php

namespace Tests\Feature;

use App\Enums\MediaKind;
use App\Models\Article;
use App\Models\Media;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaLibraryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->syncPermissions();
    }

    protected function realUpload(string $name, string $contents): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'media');
        file_put_contents($path, $contents);

        return new UploadedFile($path, $name, null, null, true);
    }

    public function test_image_upload_stores_safe_file_and_generates_renditions(): void
    {
        $this->actingAsRole('Editor');

        $this->postJson(route('admin.media.store'), [
            'file' => UploadedFile::fake()->image('Team photo <script>.jpg', 2000, 1200),
            'alt' => 'The DXCOVR team at work',
        ])->assertCreated()->assertJsonPath('media.alt', 'The DXCOVR team at work');

        $media = Media::query()->firstOrFail();

        $this->assertSame(MediaKind::Image, $media->kind);
        $this->assertSame(2000, $media->width);
        $this->assertSame(1200, $media->height);
        $this->assertMatchesRegularExpression('~^media/\d{4}/\d{2}/[0-9a-z]{26}\.jpg$~', $media->path);
        $this->assertStringNotContainsString('<script>', $media->original_name);
        Storage::disk('public')->assertExists($media->path);

        $this->assertSame([480, 960, 1600], array_keys($media->variants));
        foreach ($media->variants as $path) {
            Storage::disk('public')->assertExists($path);
            $this->assertStringEndsWith('.webp', $path);
        }

        $this->assertDatabaseHas('audit_logs', ['action' => 'media.uploaded', 'auditable_id' => $media->id]);
    }

    public function test_srcset_offers_the_full_size_original_for_sharp_screens(): void
    {
        $this->actingAsRole('Editor');

        $this->postJson(route('admin.media.store'), ['file' => UploadedFile::fake()->image('hero.jpg', 3000, 1600)])->assertCreated();

        $media = Media::query()->sole();
        $this->assertSame([480, 960, 1600, 2400], array_keys($media->variants));
        $this->assertStringEndsWith($media->url().' 3000w', $media->srcset());
    }

    public function test_very_large_photos_are_scaled_down_on_upload(): void
    {
        $this->actingAsRole('Editor');

        $this->postJson(route('admin.media.store'), ['file' => UploadedFile::fake()->image('wide.jpg', 4800, 3000)])->assertCreated();

        $media = Media::query()->sole();
        $this->assertSame([3200, 2000], [$media->width, $media->height]);
        $this->assertSame([3200, 2000], array_slice(getimagesizefromstring(Storage::disk('public')->get($media->path)), 0, 2));
    }

    public function test_images_with_too_many_pixels_get_a_clear_message(): void
    {
        config(['dxcovr.media.max_pixels' => 1_000_000]);
        $this->actingAsRole('Editor');

        $this->postJson(route('admin.media.store'), ['file' => UploadedFile::fake()->image('huge.jpg', 1200, 1000)])
            ->assertUnprocessable()
            ->assertJsonPath('errors.file.0', 'This image is 1200 × 1000 pixels, which is too large to process. Resize it to under 6000 pixels wide and try again.');

        $this->assertDatabaseCount('media', 0);
    }

    public function test_svg_logos_upload_and_are_sanitised(): void
    {
        $this->actingAsRole('Editor');
        $svg = '<svg width="166" height="73" viewBox="0 0 166 73" fill="none" xmlns="http://www.w3.org/2000/svg" onload="alert(1)">'
            .'<script>alert(document.cookie)</script><path d="M16 17H27L43 55Z" fill="black"/></svg>';

        $this->postJson(route('admin.media.store'), ['file' => $this->realUpload('Partner logo.svg', $svg), 'alt' => 'Partner logo'])
            ->assertCreated();

        $media = Media::query()->sole();
        $this->assertSame(MediaKind::Image, $media->kind);
        $this->assertSame('svg', $media->extension);
        $this->assertSame([166, 73], [$media->width, $media->height]);
        $this->assertNull($media->variants);
        $this->assertStringEndsWith('.svg', $media->url());

        $stored = Storage::disk('public')->get($media->path);
        $this->assertStringContainsString('<path d="M16 17H27L43 55Z" fill="black"/>', $stored);
        $this->assertStringNotContainsString('script', $stored);
        $this->assertStringNotContainsString('onload', $stored);
    }

    public function test_svg_with_entities_is_rejected(): void
    {
        $this->actingAsRole('Editor');
        $svg = '<?xml version="1.0"?><!DOCTYPE svg [<!ENTITY x SYSTEM "file:///etc/passwd">]><svg xmlns="http://www.w3.org/2000/svg">&x;</svg>';

        $this->postJson(route('admin.media.store'), ['file' => $this->realUpload('logo.svg', $svg)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('file');

        $this->assertDatabaseCount('media', 0);
    }

    public function test_svg_cannot_be_used_as_a_social_preview_image(): void
    {
        $this->actingAsRole('Administrator');
        $svg = Media::factory()->create(['extension' => 'svg', 'mime_type' => 'image/svg+xml', 'path' => 'media/logo.svg']);
        $png = Media::factory()->create();

        $this->put(route('admin.settings.update', 'seo'), ['settings' => ['seo-title_template' => '%s | DXCOVR', 'seo-default_og_image_id' => $svg->id]])
            ->assertSessionHasErrors('settings.seo-default_og_image_id')
            ->assertSessionDoesntHaveErrors('settings.seo-title_template');

        $article = Article::factory()->create();
        $this->put(route('admin.articles.update', $article), [
            'title' => $article->title, 'slug' => $article->slug, 'excerpt' => 'x', 'body' => '<p>Body</p>',
            'seo' => ['og_image_id' => $svg->id],
        ])->assertSessionHasErrors('seo.og_image_id');

        $this->put(route('admin.articles.update', $article), [
            'title' => $article->title, 'slug' => $article->slug, 'excerpt' => 'x', 'body' => '<p>Body</p>',
            'seo' => ['og_image_id' => $png->id],
        ])->assertSessionDoesntHaveErrors('seo.og_image_id');
    }

    public function test_media_urls_follow_the_host_the_visitor_is_using(): void
    {
        config(['app.url' => 'http://localhost:8000', 'filesystems.disks.public.url' => 'http://localhost:8000/storage']);
        Storage::fake('public', ['url' => 'http://localhost:8000/storage']);
        $media = Media::factory()->create(['disk' => 'public', 'path' => 'media/photo.jpg', 'variants' => [480 => 'media/photo-480.webp']]);

        $this->get('http://127.0.0.1:8000/');

        $this->assertSame('http://127.0.0.1:8000/storage/media/photo.jpg', $media->url());
        $this->assertSame('http://127.0.0.1:8000/storage/media/photo-480.webp', $media->variantUrl(480));
        $this->assertStringStartsWith('http://127.0.0.1:8000/storage/', (string) $media->srcset());
    }

    public function test_external_storage_urls_are_left_unchanged(): void
    {
        Storage::fake('cdn', ['url' => 'https://cdn.example.com']);
        $media = Media::factory()->create(['disk' => 'cdn', 'path' => 'media/photo.jpg']);

        $this->assertSame('https://cdn.example.com/media/photo.jpg', $media->url());
    }

    public function test_small_images_do_not_get_upscaled_renditions(): void
    {
        $this->actingAsRole('Editor');

        $this->postJson(route('admin.media.store'), ['file' => UploadedFile::fake()->image('icon.png', 400, 400)])->assertCreated();

        $this->assertNull(Media::query()->first()->variants);
    }

    public function test_disguised_executable_files_are_rejected(): void
    {
        $this->actingAsRole('Editor');

        $this->postJson(route('admin.media.store'), ['file' => $this->realUpload('photo.jpg', '<?php echo "not an image"; ?>')])
            ->assertUnprocessable()->assertJsonValidationErrors('file');

        $this->postJson(route('admin.media.store'), ['file' => $this->realUpload('shell.php', '<?php echo 1; ?>')])
            ->assertUnprocessable();

        $this->postJson(route('admin.media.store'), ['file' => $this->realUpload('logo.svg', '<html><body><script>alert(1)</script></body></html>')])
            ->assertUnprocessable();

        $this->postJson(route('admin.media.store'), ['file' => $this->realUpload('page.html', '<html><body>hi</body></html>')])
            ->assertUnprocessable();

        $this->assertDatabaseCount('media', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_pdf_documents_are_accepted(): void
    {
        $this->actingAsRole('Editor');

        $this->postJson(route('admin.media.store'), ['file' => $this->realUpload('brochure.pdf', "%PDF-1.4\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF")])->assertCreated();

        $this->assertSame(MediaKind::Document, Media::query()->first()->kind);
    }

    public function test_users_without_upload_permission_cannot_upload(): void
    {
        $this->actingAsRole('Read-only Analyst');

        $this->get(route('admin.media.index'))->assertOk();
        $this->postJson(route('admin.media.store'), ['file' => UploadedFile::fake()->image('a.jpg')])->assertForbidden();
    }

    public function test_alt_text_and_details_can_be_updated(): void
    {
        $this->actingAsRole('Editor');
        $media = Media::factory()->withoutAlt()->create();

        $this->put(route('admin.media.update', $media), ['alt' => 'A circuit board', 'title' => 'Board', 'caption' => 'Prototype', 'folder' => 'Hardware'])
            ->assertSessionHasNoErrors();

        $this->assertSame('A circuit board', $media->fresh()->alt);
        $this->assertSame('Hardware', $media->fresh()->folder);
    }

    public function test_media_in_use_cannot_be_deleted(): void
    {
        $this->actingAsRole('Editor');
        $media = Media::factory()->create();
        $article = Article::factory()->create(['featured_media_id' => $media->id]);
        $project = Project::factory()->create();
        $project->syncMediaCollection('gallery', [['media_id' => $media->id]]);

        $this->get(route('admin.media.show', $media))->assertOk()->assertSee($article->title)->assertSee($project->title);

        $this->delete(route('admin.media.destroy', $media))->assertSessionHas('error');
        $this->assertModelExists($media);

        $article->update(['featured_media_id' => null]);
        $project->syncMediaCollection('gallery', []);

        $this->delete(route('admin.media.destroy', $media))->assertRedirect(route('admin.media.index'));
        $this->assertModelMissing($media);
    }

    public function test_media_referenced_in_rich_text_counts_as_in_use(): void
    {
        $this->actingAsRole('Editor');
        $media = Media::factory()->create();
        Article::factory()->create(['body' => '<p><img src="/storage/'.$media->path.'" alt="x"></p>']);

        $this->delete(route('admin.media.destroy', $media))->assertSessionHas('error');
        $this->assertModelExists($media);
    }

    public function test_replacing_a_file_keeps_references_and_removes_old_files(): void
    {
        $this->actingAsRole('Editor');

        $this->postJson(route('admin.media.store'), ['file' => UploadedFile::fake()->image('first.jpg', 1200, 800)])->assertCreated();
        $media = Media::query()->firstOrFail();
        $oldPath = $media->path;
        $article = Article::factory()->create(['featured_media_id' => $media->id]);

        $this->post(route('admin.media.replace', $media), ['file' => UploadedFile::fake()->image('second.png', 1000, 1000)])
            ->assertSessionHasNoErrors();

        $media->refresh();
        $this->assertNotSame($oldPath, $media->path);
        $this->assertSame(1000, $media->width);
        $this->assertSame($media->id, $article->fresh()->featured_media_id);
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($media->path);
    }

    public function test_replacement_must_be_the_same_kind(): void
    {
        $this->actingAsRole('Editor');
        $this->postJson(route('admin.media.store'), ['file' => UploadedFile::fake()->image('photo.jpg', 800, 600)])->assertCreated();
        $media = Media::query()->firstOrFail();

        $this->post(route('admin.media.replace', $media), ['file' => $this->realUpload('doc.pdf', "%PDF-1.4\n%%EOF")])
            ->assertSessionHasErrors('file');
    }

    public function test_picker_returns_json_and_filters(): void
    {
        $this->actingAsRole('Editor');
        Media::factory()->create(['title' => 'Workshop photo']);
        Media::factory()->create(['title' => 'Office', 'kind' => MediaKind::Document, 'extension' => 'pdf', 'mime_type' => 'application/pdf']);

        $this->getJson(route('admin.media.picker', ['kind' => 'image', 'q' => 'Workshop']))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Workshop photo');
    }
}
