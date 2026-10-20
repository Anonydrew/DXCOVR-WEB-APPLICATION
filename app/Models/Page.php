<?php

namespace App\Models;

use App\Models\Concerns\HasPublication;
use App\Models\Concerns\HasSeo;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\TracksEditors;
use Database\Factories\PageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Editable pages.
 *
 * - "system" pages back a fixed route (home, about, services index…) and hold
 *   that route's SEO metadata and configurable sections.
 * - "standard" and "legal" pages are served at /{slug} with a rich-text body.
 */
#[Fillable(['title', 'slug', 'template', 'excerpt', 'body', 'hero_media_id', 'hero_eyebrow', 'hero_cta_label', 'hero_cta_url'])]
class Page extends Model
{
    /** @use HasFactory<PageFactory> */
    use HasFactory, HasPublication, HasSeo, HasSlug, TracksEditors;

    public const TEMPLATES = [
        'standard' => 'Standard page',
        'legal' => 'Legal / policy page',
    ];

    /**
     * Routes that system pages represent. Their slugs cannot be reused by custom pages.
     *
     * @var array<string, string>
     */
    public const SYSTEM_ROUTES = [
        'home' => 'home',
        'about' => 'about',
        'services' => 'services.index',
        'work' => 'work.index',
        'products' => 'products.index',
        'insights' => 'insights.index',
        'careers' => 'careers.index',
        'contact' => 'contact',
    ];

    public const RESERVED_SLUGS = [
        'admin', 'about', 'services', 'work', 'products', 'insights', 'careers', 'contact', 'search',
        'preview', 'newsletter', 'sitemap', 'sitemap.xml', 'robots.txt', 'storage', 'build', 'up', 'media',
        'login', 'logout', 'founder', 'team', 'api', 'onboarding',
    ];

    /**
     * System pages that open with a full-bleed photo hero, with their default
     * label, icon and call to action. Each can be overridden in the admin.
     *
     * @var array<string, array{eyebrow: string, icon: string, cta_label: string}>
     */
    public const FEATURE_HEROES = [
        'services' => ['eyebrow' => 'What we do', 'icon' => 'layers', 'cta_label' => 'Start a project'],
        'insights' => ['eyebrow' => 'DXCOVR Insights', 'icon' => 'sparkle', 'cta_label' => 'Read the latest article'],
    ];

    public function hasFeatureHero(): bool
    {
        return isset(self::FEATURE_HEROES[$this->system_key]);
    }

    public function heroImage(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'hero_media_id');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(PageSection::class)->orderBy('sort_order');
    }

    public function enabledSections(): HasMany
    {
        return $this->sections()->where('is_enabled', true);
    }

    public function isSystem(): bool
    {
        return $this->system_key !== null;
    }

    public static function system(string $key): ?self
    {
        return static::query()->where('system_key', $key)->with(['seo.ogImage', 'heroImage'])->first();
    }

    public function publicUrl(): string
    {
        if ($this->isSystem() && isset(self::SYSTEM_ROUTES[$this->system_key])) {
            return route(self::SYSTEM_ROUTES[$this->system_key]);
        }

        return route('pages.show', $this);
    }

    public function missingForPublication(): array
    {
        $missing = [];

        if (! $this->isSystem() && blank(strip_tags((string) $this->body))) {
            $missing[] = 'Page content is empty.';
        }

        return $missing;
    }
}
