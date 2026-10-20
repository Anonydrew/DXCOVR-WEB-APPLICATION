<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Cached access to editable site-wide settings with safe defaults.
 *
 * Defaults deliberately leave contact details empty so nothing fabricated is
 * ever displayed; templates hide any element whose setting is blank.
 */
class SiteSettings
{
    public const CACHE_KEY = 'settings.all';

    /**
     * @var array<string, mixed>|null
     */
    protected ?array $loaded = null;

    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'site.name' => 'DXCOVR',
            'site.tagline' => 'Technology. Thoughtfully Built.',
            'site.description' => 'DXCOVR builds smart software, connected systems and immersive digital experiences that solve real problems.',

            'contact.email' => null,
            'contact.phone' => null,
            'contact.address' => null,
            'contact.location_summary' => 'Based in Ghana. Working with organisations in Ghana and beyond.',
            'contact.hours' => null,
            'contact.whatsapp_number' => null,
            'contact.whatsapp_message' => 'Hello DXCOVR, I would like to talk about a project.',
            'social.links' => [],

            'notifications.enquiry_recipients' => [],
            'notifications.application_recipients' => [],
            'notifications.onboarding_recipients' => [],
            'notifications.enquiry_acknowledgement' => true,
            'notifications.application_acknowledgement' => true,
            'notifications.editorial_review' => true,

            'editorial.review_enabled' => true,

            'newsletter.enabled' => false,
            'newsletter.heading' => 'Notes from the studio',
            'newsletter.body' => 'Occasional updates on what we are building, learning and releasing. No spam. Unsubscribe at any time.',

            'footer.statement' => 'Smart software, connected systems and immersive experiences, built with care in Ghana.',
            'footer.cta_heading' => 'Let’s build what comes next.',
            'footer.cta_label' => 'Start a project',
            'footer.cta_url' => '/contact',
            'footer.copyright' => 'DXCOVR. All rights reserved.',

            'seo.title_template' => '%s | DXCOVR',
            'seo.default_description' => 'DXCOVR designs and builds intelligent software, business systems and immersive digital experiences that help organisations solve problems and operate smarter.',
            'seo.default_og_image_id' => null,
            'seo.discourage_indexing' => false,
            'seo.robots_extra' => null,
            'seo.organization_legal_name' => 'DXCOVR',
            'seo.organization_logo_id' => null,

            'privacy.enquiry_notice' => 'I agree that DXCOVR may use these details to respond to my enquiry. They are stored securely and only accessible to our team.',
            'careers.privacy_notice' => 'We use your application only to assess your suitability for this and closely related roles. Only authorised members of our recruitment team can access it, and we delete recruitment records after the retention period described in our privacy policy.',
            'careers.retention_days' => 365,
            'careers.general_applications_email' => null,
        ];
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $all = $this->all();

        if (array_key_exists($key, $all) && $all[$key] !== null && $all[$key] !== '') {
            return $all[$key];
        }

        return $default ?? (self::defaults()[$key] ?? null);
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        if ($this->loaded !== null) {
            return $this->loaded;
        }

        try {
            $stored = Cache::rememberForever(self::CACHE_KEY, fn () => Schema::hasTable('settings')
                ? Setting::query()->pluck('value', 'key')->all()
                : []);
        } catch (Throwable) {
            $stored = [];
        }

        return $this->loaded = array_merge(self::defaults(), $stored);
    }

    /**
     * Persist the given settings and refresh the cache.
     *
     * @param  array<string, mixed>  $values
     */
    public function set(array $values): void
    {
        foreach ($values as $key => $value) {
            Setting::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        }

        $this->flush();
    }

    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
        $this->loaded = null;
    }

    /**
     * @return list<string>
     */
    public function emails(string $key): array
    {
        return collect((array) $this->get($key, []))
            ->filter(fn ($email) => is_string($email) && filter_var($email, FILTER_VALIDATE_EMAIL))
            ->values()
            ->all();
    }

    public function whatsappUrl(): ?string
    {
        $number = preg_replace('/\D+/', '', (string) $this->get('contact.whatsapp_number'));

        if ($number === '' || $number === null) {
            return null;
        }

        return 'https://wa.me/'.$number.'?text='.rawurlencode((string) $this->get('contact.whatsapp_message'));
    }
}
