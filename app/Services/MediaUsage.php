<?php

namespace App\Services;

use App\Models\Article;
use App\Models\Media;
use App\Models\MediaAttachment;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\Product;
use App\Models\Project;
use App\Models\SeoMeta;
use App\Models\Service;
use App\Models\TeamMember;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Support\Facades\Route;

/**
 * Finds every place a media item is referenced so it is never deleted while in use.
 */
class MediaUsage
{
    public function __construct(protected SiteSettings $settings) {}

    /**
     * @return list<array{type: string, title: string, url: string|null}>
     */
    public function find(Media $media): array
    {
        $usages = [];
        $id = $media->getKey();

        $columnReferences = [
            [Article::class, 'featured_media_id', 'Article', 'title', 'admin.articles.edit'],
            [Service::class, 'hero_media_id', 'Service', 'title', 'admin.services.edit'],
            [Page::class, 'hero_media_id', 'Page hero', 'title', 'admin.pages.edit'],
            [Project::class, 'cover_media_id', 'Project cover', 'title', 'admin.projects.edit'],
            [Product::class, 'logo_media_id', 'Product logo', 'name', 'admin.products.edit'],
            [Product::class, 'hero_media_id', 'Product hero', 'name', 'admin.products.edit'],
            [TeamMember::class, 'photo_media_id', 'Team member', 'name', 'admin.team.edit'],
            [Testimonial::class, 'photo_media_id', 'Testimonial', 'author_name', 'admin.testimonials.edit'],
            [User::class, 'avatar_media_id', 'User avatar', 'name', null],
        ];

        foreach ($columnReferences as [$class, $column, $type, $titleColumn, $route]) {
            foreach ($class::query()->where($column, $id)->get() as $model) {
                $usages[] = $this->usage($type, (string) $model->{$titleColumn}, $route, $model);
            }
        }

        foreach (SeoMeta::query()->where('og_image_id', $id)->with('seoable')->get() as $seo) {
            $usages[] = ['type' => 'Social preview image', 'title' => $seo->seoable?->displayTitle() ?? 'Page', 'url' => null];
        }

        foreach (MediaAttachment::query()->where('media_id', $id)->with('attachable')->get() as $attachment) {
            $owner = $attachment->attachable;
            $route = $owner instanceof Project ? 'admin.projects.edit' : ($owner instanceof Product ? 'admin.products.edit' : null);
            $usages[] = $this->usage('Gallery', $owner?->displayTitle() ?? 'Unknown', $route, $owner);
        }

        foreach (['seo.default_og_image_id' => 'Default social image', 'seo.organization_logo_id' => 'Organisation logo'] as $key => $label) {
            if ((int) $this->settings->get($key) === $id) {
                $usages[] = ['type' => 'Setting', 'title' => $label, 'url' => Route::has('admin.settings.edit') ? route('admin.settings.edit', 'seo') : null];
            }
        }

        foreach (PageSection::query()->with('page')->get() as $section) {
            if ($this->containsMediaId($section->data ?? [], $id)) {
                $usages[] = [
                    'type' => 'Page section',
                    'title' => ($section->page?->title ?? 'Page').': '.$section->displayLabel(),
                    'url' => $section->page ? route('admin.pages.edit', $section->page) : null,
                ];
            }
        }

        $richText = [
            [Article::class, ['body'], 'Article content', 'title', 'admin.articles.edit'],
            [Page::class, ['body'], 'Page content', 'title', 'admin.pages.edit'],
            [Service::class, ['body'], 'Service content', 'title', 'admin.services.edit'],
            [Project::class, ['challenge', 'objectives', 'approach', 'solution', 'role', 'results'], 'Project content', 'title', 'admin.projects.edit'],
            [Product::class, ['body', 'audience', 'pricing'], 'Product content', 'name', 'admin.products.edit'],
        ];

        foreach ($richText as [$class, $columns, $type, $titleColumn, $route]) {
            $matches = $class::query()->where(function ($query) use ($columns, $media): void {
                foreach ($columns as $column) {
                    $query->orWhere($column, 'like', '%'.$media->path.'%');
                }
            })->get();

            foreach ($matches as $model) {
                $usages[] = $this->usage($type, (string) $model->{$titleColumn}, $route, $model);
            }
        }

        return $usages;
    }

    /**
     * @param  array<mixed>  $data
     */
    protected function containsMediaId(array $data, int $id): bool
    {
        foreach ($data as $key => $value) {
            if (is_array($value) && $this->containsMediaId($value, $id)) {
                return true;
            }

            if (is_string($key) && str_ends_with($key, 'media_id') && (int) $value === $id) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{type: string, title: string, url: string|null}
     */
    protected function usage(string $type, string $title, ?string $route, mixed $model): array
    {
        return [
            'type' => $type,
            'title' => $title,
            'url' => $route && $model && Route::has($route) ? route($route, $model) : null,
        ];
    }
}
