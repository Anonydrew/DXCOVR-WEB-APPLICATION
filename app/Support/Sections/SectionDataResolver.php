<?php

namespace App\Support\Sections;

use App\Models\Article;
use App\Models\Faq;
use App\Models\Media;
use App\Models\PageSection;
use App\Models\Product;
use App\Models\Project;
use App\Models\Service;
use App\Models\TeamMember;
use App\Models\Testimonial;
use Illuminate\Support\Collection;

/**
 * Loads the records each section type needs, in as few queries as possible.
 * Sections whose source data is empty are flagged so templates can hide them
 * instead of displaying placeholder content.
 */
class SectionDataResolver
{
    /**
     * @param  Collection<int, PageSection>  $sections
     * @return array<int, array<string, mixed>> Section ID => template data.
     */
    public function resolve(Collection $sections): array
    {
        $mediaIds = $sections->flatMap(fn (PageSection $section) => $this->mediaIds($section->data ?? []))->unique();
        $media = $mediaIds->isEmpty() ? collect() : Media::query()->whereIn('id', $mediaIds)->get()->keyBy('id');

        $resolved = [];
        $cache = [];

        foreach ($sections as $section) {
            $limit = (int) ($section->value('limit') ?: 3);

            $data = match ($section->type) {
                'capabilities' => ['services' => $cache['services'] ??= Service::query()->published()->orderByDesc('is_featured')->ordered()->with('heroImage')->get()],
                'services_showcase' => ['services' => $cache['services'] ??= Service::query()->published()->orderByDesc('is_featured')->ordered()->with('heroImage')->get()],
                'featured_projects' => ['projects' => Project::query()->published()->where('is_featured', true)->ordered()->with(['cover', 'category', 'services'])->limit($limit)->get()],
                'enquiry' => ['services' => $cache['services'] ??= Service::query()->published()->orderByDesc('is_featured')->ordered()->with('heroImage')->get()],
                'featured_products' => ['products' => Product::query()->published()->where('is_featured', true)->ordered()->with(['logo', 'heroImage'])->limit($limit)->get()],
                'latest_articles' => ['articles' => Article::query()->published()->with(['featuredImage', 'categories', 'author'])->latest('published_at')->limit($limit)->get()],
                'founder_intro' => ['founder' => $cache['founder'] ??= TeamMember::query()->where('is_founder', true)->published()->with('photo')->first()],
                'team' => ['members' => TeamMember::query()->published()->where('is_founder', false)->with('photo')->orderBy('sort_order')->get()],
                'testimonials' => ['testimonials' => Testimonial::query()->published()->orderBy('sort_order')->limit($limit)->get()],
                'faq' => ['faqs' => Faq::query()->published()->where('group', $section->value('group', 'general'))->whereNull('service_id')->whereNull('product_id')->orderBy('sort_order')->get()],
                default => [],
            };

            $data['media'] = $section->value('media_id') ? $media->get((int) $section->value('media_id')) : null;
            $data['library'] = $media;
            $data['isEmpty'] = $this->isEmpty($section, $data);

            $resolved[$section->id] = $data;
        }

        return $resolved;
    }

    /**
     * Every media ID referenced by a section, including inside repeater rows.
     *
     * @param  array<mixed>  $data
     * @return list<int>
     */
    protected function mediaIds(array $data): array
    {
        $ids = [];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $ids = array_merge($ids, $this->mediaIds($value));
            } elseif (is_string($key) && str_ends_with($key, 'media_id') && $value) {
                $ids[] = (int) $value;
            }
        }

        return $ids;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function isEmpty(PageSection $section, array $data): bool
    {
        return match ($section->type) {
            'capabilities' => $data['services']->isEmpty(),
            'featured_projects' => $data['projects']->isEmpty(),
            'featured_products' => $data['products']->isEmpty(),
            'latest_articles' => $data['articles']->isEmpty(),
            'team' => $data['members']->isEmpty(),
            'testimonials' => $data['testimonials']->isEmpty(),
            'faq' => $data['faqs']->isEmpty(),
            'highlights', 'feature_grid', 'steps', 'milestones', 'spotlight', 'quick_links', 'clients', 'link_cards' => empty($section->value('items')),
            'services_showcase' => $data['services']->isEmpty(),
            'mission' => empty($section->value('columns')),
            'about_overview' => blank($section->value('lead')) && blank($section->value('body')) && empty($section->value('stats')),
            'rich_text' => blank($section->value('body')),
            default => false,
        };
    }
}
