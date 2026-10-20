<?php

namespace App\Support\Seo;

use App\Models\Media;
use App\Models\SeoMeta;
use App\Services\SiteSettings;
use Illuminate\Support\Str;

/**
 * Fluent builder for a page's search and social metadata.
 *
 * Editor-supplied SeoMeta overrides take precedence over values derived from
 * the content; site defaults fill anything left empty.
 */
final class Seo
{
    protected ?string $title = null;

    protected ?string $description = null;

    protected ?string $canonical = null;

    protected ?Media $image = null;

    protected string $type = 'website';

    protected bool $noindex = false;

    protected ?SeoMeta $meta = null;

    protected ?string $publishedTime = null;

    protected ?string $modifiedTime = null;

    /**
     * @var list<array{name: string, url: string}>
     */
    protected array $breadcrumbs = [];

    /**
     * @var list<array<string, mixed>>
     */
    protected array $schema = [];

    public static function make(?string $title = null): self
    {
        $seo = new self;
        $seo->title = $title;

        return $seo;
    }

    public function description(?string $description): self
    {
        $this->description = $description ? Str::limit(trim(strip_tags($description)), 300) : null;

        return $this;
    }

    public function canonical(?string $url): self
    {
        $this->canonical = $url;

        return $this;
    }

    public function image(?Media $image): self
    {
        $this->image = $image;

        return $this;
    }

    public function type(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function noindex(bool $noindex = true): self
    {
        $this->noindex = $noindex;

        return $this;
    }

    public function meta(?SeoMeta $meta): self
    {
        $this->meta = $meta;

        return $this;
    }

    public function times(?\DateTimeInterface $published, ?\DateTimeInterface $modified = null): self
    {
        $this->publishedTime = $published?->format(DATE_ATOM);
        $this->modifiedTime = $modified?->format(DATE_ATOM);

        return $this;
    }

    /**
     * @param  list<array{name: string, url: string}>  $breadcrumbs
     */
    public function breadcrumbs(array $breadcrumbs): self
    {
        $this->breadcrumbs = $breadcrumbs;

        return $this;
    }

    /**
     * @param  array<string, mixed>  $schema
     */
    public function schema(array $schema): self
    {
        $this->schema[] = $schema;

        return $this;
    }

    public function siteName(): string
    {
        return (string) app(SiteSettings::class)->get('site.name', 'DXCOVR');
    }

    public function pageTitle(): string
    {
        return $this->meta?->meta_title ?: ($this->title ?: $this->siteName());
    }

    public function fullTitle(): string
    {
        $title = $this->pageTitle();

        if ($title === $this->siteName() || Str::contains($title, $this->siteName())) {
            return $title === $this->siteName()
                ? $this->siteName().' | '.app(SiteSettings::class)->get('site.tagline')
                : $title;
        }

        $template = (string) app(SiteSettings::class)->get('seo.title_template', '%s | DXCOVR');

        return str_contains($template, '%s') ? sprintf($template, $title) : $title.' | '.$this->siteName();
    }

    public function metaDescription(): string
    {
        return $this->meta?->meta_description
            ?: ($this->description ?: (string) app(SiteSettings::class)->get('seo.default_description'));
    }

    public function canonicalUrl(): string
    {
        if (filled($this->meta?->canonical_url)) {
            return $this->meta->canonical_url;
        }

        if ($this->canonical) {
            return $this->canonical;
        }

        $page = (int) request()->query('page', 1);

        return url()->current().($page > 1 ? '?page='.$page : '');
    }

    public function ogTitle(): string
    {
        return $this->meta?->og_title ?: $this->pageTitle();
    }

    public function ogDescription(): string
    {
        return $this->meta?->og_description ?: $this->metaDescription();
    }

    public function imageMedia(): ?Media
    {
        if ($this->meta?->ogImage) {
            return $this->meta->ogImage;
        }

        // Social networks cannot display SVG, so an SVG page image falls back to the default.
        if ($this->image?->isImage() && $this->image->extension !== 'svg') {
            return $this->image;
        }

        $defaultId = app(SiteSettings::class)->get('seo.default_og_image_id');

        return $defaultId ? Media::query()->find($defaultId) : null;
    }

    public function imageUrl(): ?string
    {
        $media = $this->imageMedia();

        return $media ? $media->variantUrl(1600) : null;
    }

    public function ogType(): string
    {
        return $this->type;
    }

    public function robots(): string
    {
        $discourage = (bool) app(SiteSettings::class)->get('seo.discourage_indexing', false);

        return ($this->noindex || $discourage || ($this->meta?->noindex ?? false))
            ? 'noindex, nofollow'
            : 'index, follow, max-image-preview:large';
    }

    public function publishedTime(): ?string
    {
        return $this->publishedTime;
    }

    public function modifiedTime(): ?string
    {
        return $this->modifiedTime;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function jsonLd(): array
    {
        $graphs = $this->schema;

        if ($this->breadcrumbs !== []) {
            $graphs[] = [
                '@context' => 'https://schema.org',
                '@type' => 'BreadcrumbList',
                'itemListElement' => collect($this->breadcrumbs)->values()->map(fn (array $crumb, int $index) => [
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'name' => $crumb['name'],
                    'item' => $crumb['url'],
                ])->all(),
            ];
        }

        return $graphs;
    }

    /**
     * @return list<array{name: string, url: string}>
     */
    public function breadcrumbItems(): array
    {
        return $this->breadcrumbs;
    }
}
