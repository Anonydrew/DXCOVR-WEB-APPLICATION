<?php

namespace App\Support\Sections;

use App\Models\Faq;
use App\Models\Service;
use App\Rules\SafeUrl;
use App\Services\HtmlSanitizer;

/**
 * Predefined page section types and their editable fields.
 *
 * Layouts are fixed in Blade (resources/views/site/sections/*); editors only
 * control validated content and a small set of options, which preserves the
 * design system while keeping pages flexible.
 *
 * Field types: text, textarea, richtext, url, media, toggle, number, select, repeater.
 */
final class SectionRegistry
{
    /**
     * Card styles for the spotlight carousel.
     */
    public const CARD_TONES = [
        'dark' => 'Dark',
        'lime' => 'Lime',
        'light' => 'Light',
    ];

    /**
     * Icons available to quick links (rendered by <x-icon>).
     */
    public const LINK_ICONS = [
        'layers' => 'Services',
        'briefcase' => 'Work',
        'box' => 'Product',
        'file' => 'Article',
        'users' => 'People',
        'mail' => 'Email',
        'phone' => 'Phone',
        'search' => 'Search',
        'globe' => 'Globe',
        'shield' => 'Security',
        'sparkle' => 'Spark',
        'grid' => 'Grid',
    ];

    /**
     * @return array<string, array{label: string, description: string, fields: array<string, array<string, mixed>>}>
     */
    public static function definitions(): array
    {
        $eyebrow = ['type' => 'text', 'label' => 'Eyebrow label', 'help' => 'Short label shown above the heading.', 'max' => 60];
        $heading = ['type' => 'text', 'label' => 'Heading', 'max' => 160];
        $intro = ['type' => 'textarea', 'label' => 'Introduction', 'max' => 600];
        $ctaLabel = ['type' => 'text', 'label' => 'Link label', 'max' => 60];
        $ctaUrl = ['type' => 'url', 'label' => 'Link destination'];
        $limit = fn (int $default, int $max) => ['type' => 'number', 'label' => 'Number of items', 'default' => $default, 'min' => 1, 'max' => $max];

        return [
            'hero' => [
                'label' => 'Hero',
                'description' => 'Full-width opening section: background image, headline, buttons, floating interface cards, a strip of proof cards and a logo row.',
                'fields' => [
                    'eyebrow' => $eyebrow,
                    'headline' => ['type' => 'text', 'label' => 'Headline', 'required' => true, 'max' => 160],
                    'subheadline' => ['type' => 'textarea', 'label' => 'Supporting copy', 'max' => 400],
                    'primary_label' => ['type' => 'text', 'label' => 'Primary button label', 'max' => 40],
                    'primary_url' => ['type' => 'url', 'label' => 'Primary button destination'],
                    'secondary_label' => ['type' => 'text', 'label' => 'Secondary button label', 'max' => 40],
                    'secondary_url' => ['type' => 'url', 'label' => 'Secondary button destination'],
                    'media_id' => ['type' => 'media', 'label' => 'Background image', 'help' => 'Full-width photo behind the headline (landscape, at least 2400 px wide; keep the subject on the right). A dark overlay keeps the text readable. Without an image, DXCOVR artwork is shown.'],
                    'show_ui_cards' => ['type' => 'toggle', 'label' => 'Show floating interface cards', 'default' => true],
                    'proof_heading' => ['type' => 'text', 'label' => 'Proof strip label', 'max' => 60, 'help' => 'Small heading above the proof cards.'],
                    'proof_items' => ['type' => 'repeater', 'label' => 'Proof cards', 'max_items' => 8, 'help' => 'With three or more cards the strip scrolls slowly; visitors can pause it. Only use figures you can evidence.', 'fields' => [
                        'value' => ['type' => 'text', 'label' => 'Highlight (e.g. a number or short word)', 'required' => true, 'max' => 12],
                        'label' => ['type' => 'text', 'label' => 'Highlight caption', 'max' => 40],
                        'title' => ['type' => 'text', 'label' => 'Source line (e.g. product or client name)', 'max' => 60],
                        'media_id' => ['type' => 'media', 'label' => 'Source logo (optional, replaces the source line)'],
                        'body' => ['type' => 'textarea', 'label' => 'Description', 'max' => 160],
                        'url' => ['type' => 'url', 'label' => 'Link destination'],
                    ]],
                    'logos' => ['type' => 'repeater', 'label' => 'Client or partner logos', 'max_items' => 16, 'help' => 'Only add organisations you have worked with and have written permission to name. Upload a transparent PNG; logos are shown as white silhouettes. While empty, the discipline names below are shown instead.', 'fields' => [
                        'media_id' => ['type' => 'media', 'label' => 'Logo image'],
                        'name' => ['type' => 'text', 'label' => 'Organisation name', 'required' => true, 'max' => 80],
                    ]],
                    'disciplines' => ['type' => 'repeater', 'label' => 'Discipline names (shown when there are no logos)', 'max_items' => 10, 'fields' => [
                        'label' => ['type' => 'text', 'label' => 'Label', 'required' => true, 'max' => 30],
                    ]],
                ],
            ],
            'spotlight' => [
                'label' => 'Spotlight carousel',
                'description' => 'A horizontally scrolling row of campaign cards with a badge, title, expandable description and button. Cards without an image use DXCOVR artwork.',
                'fields' => [
                    'eyebrow' => $eyebrow,
                    'heading' => $heading + ['help' => 'Optional. Leave blank for a heading-free carousel directly under the hero.'],
                    'items' => ['type' => 'repeater', 'label' => 'Cards', 'max_items' => 10, 'fields' => [
                        'badge' => ['type' => 'text', 'label' => 'Badge (optional)', 'max' => 30],
                        'title' => ['type' => 'text', 'label' => 'Title', 'required' => true, 'max' => 90],
                        'body' => ['type' => 'textarea', 'label' => 'Expandable description', 'max' => 300],
                        'media_id' => ['type' => 'media', 'label' => 'Image (optional)'],
                        'icon' => ['type' => 'select', 'label' => 'Artwork motif (used without an image)', 'options' => Service::ICONS],
                        'tone' => ['type' => 'select', 'label' => 'Card style (without an image)', 'options' => self::CARD_TONES],
                        'cta_label' => ['type' => 'text', 'label' => 'Button label', 'max' => 40],
                        'cta_url' => ['type' => 'url', 'label' => 'Button destination'],
                    ]],
                ],
            ],
            'quick_links' => [
                'label' => 'Quick links',
                'description' => 'A centred row of icon links to key destinations.',
                'fields' => [
                    'heading' => $heading + ['help' => 'Optional. Shown above the links.'],
                    'items' => ['type' => 'repeater', 'label' => 'Links', 'max_items' => 8, 'fields' => [
                        'icon' => ['type' => 'select', 'label' => 'Icon', 'options' => self::LINK_ICONS],
                        'label' => ['type' => 'text', 'label' => 'Label', 'required' => true, 'max' => 40],
                        'url' => ['type' => 'url', 'label' => 'Destination', 'required' => true],
                    ]],
                ],
            ],
            'statement' => [
                'label' => 'Positioning statement',
                'description' => 'A large editorial statement with optional supporting paragraph.',
                'fields' => [
                    'eyebrow' => $eyebrow,
                    'statement' => ['type' => 'textarea', 'label' => 'Statement', 'required' => true, 'max' => 400],
                    'body' => ['type' => 'textarea', 'label' => 'Supporting paragraph', 'max' => 1200],
                ],
            ],
            'capabilities' => [
                'label' => 'Capabilities (services)',
                'description' => 'Lists published services, featured services first.',
                'fields' => [
                    'eyebrow' => $eyebrow, 'heading' => $heading, 'intro' => $intro,
                    'limit' => $limit(7, 12),
                    'cta_label' => $ctaLabel, 'cta_url' => $ctaUrl,
                ],
            ],
            'featured_projects' => [
                'label' => 'Selected case studies',
                'description' => 'A full-width gallery of featured, published projects: image, title and one short line each. Hidden automatically when there are none.',
                'fields' => [
                    'heading' => $heading,
                    'limit' => $limit(4, 7),
                    'cta_label' => $ctaLabel, 'cta_url' => $ctaUrl,
                ],
            ],
            'featured_products' => [
                'label' => 'Featured products',
                'description' => 'Featured, published DXCOVR products. Hidden automatically when there are none.',
                'fields' => [
                    'eyebrow' => $eyebrow, 'heading' => $heading, 'intro' => $intro,
                    'limit' => $limit(2, 6),
                    'cta_label' => $ctaLabel, 'cta_url' => $ctaUrl,
                ],
            ],
            'highlights' => [
                'label' => 'Technology highlights',
                'description' => 'Editorial tiles for focus areas such as AI, immersive technology and engineering.',
                'fields' => [
                    'eyebrow' => $eyebrow, 'heading' => $heading, 'intro' => $intro,
                    'items' => ['type' => 'repeater', 'label' => 'Highlights', 'max_items' => 6, 'fields' => [
                        'label' => ['type' => 'text', 'label' => 'Label', 'max' => 40],
                        'title' => ['type' => 'text', 'label' => 'Title', 'required' => true, 'max' => 120],
                        'body' => ['type' => 'textarea', 'label' => 'Description', 'max' => 400],
                        'icon' => ['type' => 'select', 'label' => 'Motif', 'options' => Service::ICONS],
                        'url' => ['type' => 'url', 'label' => 'Link destination'],
                        'link_label' => ['type' => 'text', 'label' => 'Link label', 'max' => 40],
                    ]],
                ],
            ],
            'latest_articles' => [
                'label' => 'Latest insights',
                'description' => 'The most recent published articles. Hidden automatically when there are none.',
                'fields' => [
                    'eyebrow' => $eyebrow, 'heading' => $heading, 'intro' => $intro,
                    'layout' => ['type' => 'select', 'label' => 'Layout', 'options' => ['grid' => 'Grid of cards', 'list' => 'List of wide cards'], 'default' => 'grid'],
                    'limit' => $limit(3, 6),
                    'cta_label' => $ctaLabel, 'cta_url' => $ctaUrl,
                ],
            ],
            'founder_intro' => [
                'label' => 'Company & founder introduction',
                'description' => 'Introduces DXCOVR and, optionally, the founder profile.',
                'fields' => [
                    'eyebrow' => $eyebrow, 'heading' => $heading,
                    'body' => ['type' => 'textarea', 'label' => 'Body', 'max' => 1500],
                    'show_founder' => ['type' => 'toggle', 'label' => 'Show founder card', 'default' => true],
                    'cta_label' => $ctaLabel, 'cta_url' => $ctaUrl,
                ],
            ],
            'enquiry' => [
                'label' => 'Project enquiry',
                'description' => 'Invitation to start a project, optionally with the enquiry form inline.',
                'fields' => [
                    'eyebrow' => $eyebrow, 'heading' => $heading,
                    'body' => ['type' => 'textarea', 'label' => 'Body', 'max' => 800],
                    'show_form' => ['type' => 'toggle', 'label' => 'Show the enquiry form', 'default' => true],
                ],
            ],
            'newsletter' => [
                'label' => 'Newsletter sign-up',
                'description' => 'Only displayed when the newsletter is enabled in Settings.',
                'fields' => [
                    'heading' => $heading,
                    'body' => ['type' => 'textarea', 'label' => 'Body', 'max' => 400],
                ],
            ],
            'rich_text' => [
                'label' => 'Rich text',
                'description' => 'A heading and formatted body copy.',
                'fields' => [
                    'eyebrow' => $eyebrow, 'heading' => $heading,
                    'body' => ['type' => 'richtext', 'label' => 'Body', 'max' => 30000],
                ],
            ],
            'feature_grid' => [
                'label' => 'Feature grid',
                'description' => 'A grid of short points, such as values, principles or areas of expertise.',
                'fields' => [
                    'eyebrow' => $eyebrow, 'heading' => $heading, 'intro' => $intro,
                    'items' => ['type' => 'repeater', 'label' => 'Items', 'max_items' => 12, 'fields' => [
                        'title' => ['type' => 'text', 'label' => 'Title', 'required' => true, 'max' => 120],
                        'body' => ['type' => 'textarea', 'label' => 'Description', 'max' => 500],
                        'icon' => ['type' => 'select', 'label' => 'Motif (optional)', 'options' => ['' => 'None'] + Service::ICONS],
                    ]],
                ],
            ],
            'steps' => [
                'label' => 'Process steps',
                'description' => 'A numbered sequence, such as how DXCOVR works.',
                'fields' => [
                    'eyebrow' => $eyebrow, 'heading' => $heading, 'intro' => $intro,
                    'items' => ['type' => 'repeater', 'label' => 'Steps', 'max_items' => 8, 'fields' => [
                        'title' => ['type' => 'text', 'label' => 'Title', 'required' => true, 'max' => 120],
                        'body' => ['type' => 'textarea', 'label' => 'Description', 'max' => 500],
                    ]],
                ],
            ],
            'media_feature' => [
                'label' => 'Image and text',
                'description' => 'An image alongside a heading, copy and an optional link.',
                'fields' => [
                    'eyebrow' => $eyebrow, 'heading' => $heading,
                    'body' => ['type' => 'textarea', 'label' => 'Body', 'max' => 1500],
                    'media_id' => ['type' => 'media', 'label' => 'Image'],
                    'layout' => ['type' => 'select', 'label' => 'Image position', 'options' => ['right' => 'Image on the right', 'left' => 'Image on the left'], 'default' => 'right'],
                    'cta_label' => $ctaLabel, 'cta_url' => $ctaUrl,
                ],
            ],
            'team' => [
                'label' => 'Team',
                'description' => 'Published team profiles (excluding the founder). Hidden when there are none.',
                'fields' => ['eyebrow' => $eyebrow, 'heading' => $heading, 'intro' => $intro],
            ],
            'clients' => [
                'label' => 'Clients',
                'description' => 'Client logos with a line about the work delivered. Hidden until at least one client is added.',
                'fields' => [
                    'heading' => $heading + ['default' => 'Clients'],
                    'intro' => $intro,
                    'items' => ['type' => 'repeater', 'label' => 'Clients', 'max_items' => 18, 'help' => 'Only list organisations you have worked with and have written permission to name. Upload transparent PNG logos; they are shown in one colour.', 'fields' => [
                        'media_id' => ['type' => 'media', 'label' => 'Logo (optional, the name is shown without one)'],
                        'name' => ['type' => 'text', 'label' => 'Client name', 'required' => true, 'max' => 80],
                        'body' => ['type' => 'textarea', 'label' => 'What we delivered', 'required' => true, 'max' => 200],
                        'url' => ['type' => 'url', 'label' => 'Case study link (optional)'],
                    ]],
                ],
            ],
            'testimonials' => [
                'label' => 'Testimonials',
                'description' => 'Published testimonials with confirmed consent. Hidden when there are none.',
                'fields' => ['eyebrow' => $eyebrow, 'heading' => $heading, 'limit' => $limit(3, 9)],
            ],
            'faq' => [
                'label' => 'Frequently asked questions',
                'description' => 'Published FAQs from one group. Hidden when the group is empty.',
                'fields' => [
                    'eyebrow' => $eyebrow, 'heading' => $heading,
                    'group' => ['type' => 'select', 'label' => 'FAQ group', 'options' => Faq::GROUPS, 'default' => 'general'],
                ],
            ],
            'milestones' => [
                'label' => 'Milestones',
                'description' => 'A scrolling timeline of verified company milestones. Hidden when empty.',
                'fields' => [
                    'eyebrow' => $eyebrow + ['help' => 'For example the years covered, such as “2024 to today”.'],
                    'heading' => $heading,
                    'intro' => $intro,
                    'items' => ['type' => 'repeater', 'label' => 'Milestones', 'max_items' => 20, 'help' => 'Only add milestones that really happened.', 'fields' => [
                        'period' => ['type' => 'text', 'label' => 'Date or year', 'required' => true, 'max' => 40],
                        'title' => ['type' => 'text', 'label' => 'What happened', 'required' => true, 'max' => 160],
                        'body' => ['type' => 'textarea', 'label' => 'More detail (optional)', 'max' => 400],
                        'media_id' => ['type' => 'media', 'label' => 'Photo (optional)'],
                    ]],
                ],
            ],
            'about_overview' => [
                'label' => 'Overview and figures',
                'description' => 'Two short paragraphs followed by a grid of figure cards.',
                'fields' => [
                    'lead' => ['type' => 'textarea', 'label' => 'Main statement (shown in bold)', 'max' => 400],
                    'body' => ['type' => 'textarea', 'label' => 'Supporting text', 'max' => 600],
                    'stats' => ['type' => 'repeater', 'label' => 'Figures', 'max_items' => 6, 'help' => 'Only use figures you can evidence.', 'fields' => [
                        'value' => ['type' => 'text', 'label' => 'Figure', 'required' => true, 'max' => 16],
                        'label' => ['type' => 'text', 'label' => 'What it counts', 'required' => true, 'max' => 60],
                        'note' => ['type' => 'text', 'label' => 'Small note (optional)', 'max' => 80],
                    ]],
                ],
            ],
            'services_showcase' => [
                'label' => 'Services carousel',
                'description' => 'Published services, one at a time, with image, summary and a button. Hidden when there are none.',
                'fields' => [
                    'heading' => $heading, 'intro' => $intro,
                    'cta_label' => ['type' => 'text', 'label' => 'Button text', 'max' => 40, 'default' => 'Explore this service'],
                ],
            ],
            'mission' => [
                'label' => 'Mission band',
                'description' => 'An optional wide banner image, then a dark band with up to three short columns and two photos.',
                'fields' => [
                    'media_id' => ['type' => 'media', 'label' => 'Banner image (optional)'],
                    'heading' => $heading,
                    'columns' => ['type' => 'repeater', 'label' => 'Columns', 'max_items' => 3, 'fields' => [
                        'body' => ['type' => 'textarea', 'label' => 'Text', 'required' => true, 'max' => 400],
                        'link_label' => ['type' => 'text', 'label' => 'Link text (optional)', 'max' => 40],
                        'link_url' => ['type' => 'url', 'label' => 'Link destination'],
                    ]],
                    'photo_one_media_id' => ['type' => 'media', 'label' => 'Photo one (optional)'],
                    'photo_two_media_id' => ['type' => 'media', 'label' => 'Photo two (optional)'],
                ],
            ],
            'link_cards' => [
                'label' => 'Link cards',
                'description' => 'A scrolling row of tall photo cards, each with a label and a button.',
                'fields' => [
                    'heading' => $heading,
                    'items' => ['type' => 'repeater', 'label' => 'Cards', 'max_items' => 8, 'fields' => [
                        'label' => ['type' => 'text', 'label' => 'Label', 'required' => true, 'max' => 40],
                        'button_label' => ['type' => 'text', 'label' => 'Button text', 'required' => true, 'max' => 40],
                        'url' => ['type' => 'url', 'label' => 'Button link', 'required' => true],
                        'media_id' => ['type' => 'media', 'label' => 'Photo (optional, portrait works best)'],
                        'glyph' => ['type' => 'select', 'label' => 'Artwork when there is no photo', 'options' => Service::ICONS],
                    ]],
                ],
            ],
            'cta_banner' => [
                'label' => 'Call to action banner',
                'description' => 'A full-width prompt with one button.',
                'fields' => [
                    'heading' => $heading + ['required' => true],
                    'body' => ['type' => 'textarea', 'label' => 'Body', 'max' => 500],
                    'cta_label' => $ctaLabel, 'cta_url' => $ctaUrl,
                ],
            ],
        ];
    }

    /**
     * @return array{label: string, description: string, fields: array<string, array<string, mixed>>}|null
     */
    public static function definition(string $type): ?array
    {
        return self::definitions()[$type] ?? null;
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::definitions())->map(fn (array $definition) => $definition['label'])->all();
    }

    /**
     * Validation rules for a section's data payload, keyed under "data.".
     *
     * @return array<string, mixed>
     */
    public static function rules(string $type): array
    {
        $definition = self::definition($type);

        if ($definition === null) {
            return [];
        }

        $rules = ['data' => ['nullable', 'array']];

        foreach ($definition['fields'] as $name => $field) {
            $rules = array_merge($rules, self::fieldRules('data.'.$name, $field));
        }

        return $rules;
    }

    /**
     * @param  array<string, mixed>  $field
     * @return array<string, mixed>
     */
    protected static function fieldRules(string $key, array $field): array
    {
        $required = ! empty($field['required']) ? 'required' : 'nullable';

        return match ($field['type']) {
            'text' => [$key => [$required, 'string', 'max:'.($field['max'] ?? 255)]],
            'textarea' => [$key => [$required, 'string', 'max:'.($field['max'] ?? 2000)]],
            'richtext' => [$key => [$required, 'string', 'max:'.($field['max'] ?? 30000)]],
            'url' => [$key => [$required, 'string', 'max:500', new SafeUrl]],
            'media' => [$key => [$required, 'integer', 'exists:media,id']],
            'toggle' => [$key => ['nullable', 'boolean']],
            'number' => [$key => [$required, 'integer', 'min:'.($field['min'] ?? 0), 'max:'.($field['max'] ?? 100)]],
            'select' => [$key => [$required, 'string', 'in:'.implode(',', array_keys($field['options']))]],
            'repeater' => array_merge(
                [$key => ['nullable', 'array', 'max:'.($field['max_items'] ?? 20)]],
                ...array_map(
                    fn (string $name, array $subField) => self::fieldRules($key.'.*.'.$name, $subField),
                    array_keys($field['fields']),
                    $field['fields'],
                ),
            ),
            default => [],
        };
    }

    /**
     * Remove repeater rows where every value is empty, before validation runs.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function withoutBlankRows(string $type, array $data): array
    {
        foreach (self::definition($type)['fields'] ?? [] as $name => $field) {
            if ($field['type'] !== 'repeater' || ! isset($data[$name]) || ! is_array($data[$name])) {
                continue;
            }

            // Select fields always carry a default, so they do not make a row "filled in".
            $contentFields = collect($field['fields'])->reject(fn ($subField) => $subField['type'] === 'select')->keys()->all();

            $data[$name] = array_values(array_filter($data[$name], fn ($row) => is_array($row)
                && collect($row)->only($contentFields)->filter(fn ($value) => $value !== null && $value !== '')->isNotEmpty()));
        }

        return $data;
    }

    /**
     * Human readable attribute names for validation messages.
     *
     * @return array<string, string>
     */
    public static function attributes(string $type, string $prefix = 'data.'): array
    {
        $attributes = [];

        foreach (self::definition($type)['fields'] ?? [] as $name => $field) {
            $attributes[$prefix.$name] = strtolower($field['label']);

            foreach ($field['fields'] ?? [] as $subName => $subField) {
                $attributes[$prefix.$name.'.*.'.$subName] = strtolower($field['label'].' '.$subField['label']);
            }
        }

        return $attributes;
    }

    /**
     * Cast, sanitise and strip unknown keys from validated section data.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function normalise(string $type, array $data): array
    {
        $definition = self::definition($type);

        return $definition ? self::normaliseFields($definition['fields'], $data) : [];
    }

    /**
     * @param  array<string, array<string, mixed>>  $fields
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected static function normaliseFields(array $fields, array $data): array
    {
        $clean = [];

        foreach ($fields as $name => $field) {
            $value = $data[$name] ?? null;

            $clean[$name] = match ($field['type']) {
                'toggle' => (bool) $value,
                'number' => $value === null || $value === '' ? ($field['default'] ?? null) : (int) $value,
                'media' => $value === null || $value === '' ? null : (int) $value,
                'richtext' => app(HtmlSanitizer::class)->rich(is_string($value) ? $value : null),
                'repeater' => collect(is_array($value) ? $value : [])
                    ->map(fn ($row) => self::normaliseFields($field['fields'], is_array($row) ? $row : []))
                    ->filter(fn (array $row) => collect($row)->filter(fn ($cell) => $cell !== null && $cell !== '' && $cell !== false)->isNotEmpty())
                    ->values()
                    ->all(),
                default => is_string($value) ? trim($value) : $value,
            };
        }

        return $clean;
    }

    /**
     * Default data for a new section of the given type.
     *
     * @return array<string, mixed>
     */
    public static function defaults(string $type): array
    {
        $definition = self::definition($type);

        if ($definition === null) {
            return [];
        }

        return collect($definition['fields'])
            ->map(fn (array $field) => $field['default'] ?? ($field['type'] === 'repeater' ? [] : null))
            ->all();
    }
}
