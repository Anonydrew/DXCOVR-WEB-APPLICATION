<?php

namespace App\Support\Onboarding;

use App\Models\Enquiry;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

/**
 * The client onboarding questionnaire: sections, questions, visual options
 * and validation. The client form, the admin review screen and the stored
 * answers (answers[section][field]) are all driven by this definition.
 */
final class QuestionnaireForm
{
    /**
     * Visual styles shown as small, original mock-ups. Each preview is
     * defined by a few design tokens rendered with CSS (no third-party assets).
     *
     * @var array<string, array{label: string, description: string, bg: string, surface: string, ink: string, accent: string, radius: string, font: string}>
     */
    public const STYLES = [
        'minimal' => ['label' => 'Minimal & clean', 'description' => 'Generous white space, simple type and very few colours.', 'bg' => '#ffffff', 'surface' => '#f1f2f0', 'ink' => '#141614', 'accent' => '#141614', 'radius' => '2px', 'font' => 'sans'],
        'bold' => ['label' => 'Bold & vibrant', 'description' => 'Big headlines, strong colour and confident contrast.', 'bg' => '#ff4d2e', 'surface' => '#ffd23f', 'ink' => '#160b05', 'accent' => '#160b05', 'radius' => '10px', 'font' => 'display'],
        'dark' => ['label' => 'Dark & premium', 'description' => 'Deep backgrounds, glowing accents and a high-tech feel.', 'bg' => '#0b0d10', 'surface' => '#1b1f26', 'ink' => '#f4f6f8', 'accent' => '#7c5cff', 'radius' => '8px', 'font' => 'sans'],
        'warm' => ['label' => 'Warm & friendly', 'description' => 'Soft tones, rounded shapes and an approachable voice.', 'bg' => '#fff4e8', 'surface' => '#ffd9b8', 'ink' => '#3b2416', 'accent' => '#e07a3f', 'radius' => '16px', 'font' => 'rounded'],
        'corporate' => ['label' => 'Corporate & trustworthy', 'description' => 'Structured layouts, calm blues and clear information.', 'bg' => '#f5f8fc', 'surface' => '#dce7f5', 'ink' => '#0e2a4f', 'accent' => '#1f5fbf', 'radius' => '4px', 'font' => 'sans'],
        'playful' => ['label' => 'Playful & illustrative', 'description' => 'Illustrations, cheerful colours and personality.', 'bg' => '#f3edff', 'surface' => '#b9f3dd', 'ink' => '#2b1a52', 'accent' => '#ff6fb5', 'radius' => '22px', 'font' => 'rounded'],
        'editorial' => ['label' => 'Editorial & elegant', 'description' => 'Refined serif type, large imagery and a magazine feel.', 'bg' => '#f7f3ec', 'surface' => '#e5ddcf', 'ink' => '#1d1a16', 'accent' => '#8a6d3b', 'radius' => '0px', 'font' => 'serif'],
        'futuristic' => ['label' => 'Futuristic & immersive', 'description' => '3D, motion and gradients for products that feel ahead.', 'bg' => '#060b1f', 'surface' => '#14234d', 'ink' => '#e9f2ff', 'accent' => '#25e6c9', 'radius' => '12px', 'font' => 'mono'],
    ];

    /**
     * @var array<string, array{label: string, colours: list<string>}>
     */
    public const PALETTES = [
        'ocean' => ['label' => 'Ocean trust', 'colours' => ['#0b2545', '#13315c', '#1f6fb2', '#8db8e6', '#eef4ed']],
        'forest' => ['label' => 'Forest calm', 'colours' => ['#1b2e1f', '#2f5d3a', '#6a9c5b', '#c5d8a4', '#f4f1e6']],
        'sunset' => ['label' => 'Sunset warmth', 'colours' => ['#3d1c34', '#c0392b', '#f0763b', '#f9c74f', '#fff3e2']],
        'mono' => ['label' => 'Monochrome', 'colours' => ['#0d0d0d', '#3a3a3a', '#8a8a8a', '#d6d6d6', '#ffffff']],
        'pastel' => ['label' => 'Soft pastels', 'colours' => ['#cdb4db', '#ffc8dd', '#ffafcc', '#bde0fe', '#a2d2ff']],
        'neon' => ['label' => 'Neon tech', 'colours' => ['#0a0a12', '#1e1b4b', '#7c3aed', '#22d3ee', '#a3e635']],
        'earth' => ['label' => 'Earth tones', 'colours' => ['#3e2c23', '#7f5539', '#b08968', '#ddb892', '#ede0d4']],
        'royal' => ['label' => 'Royal luxury', 'colours' => ['#14051f', '#3c096c', '#7b2cbf', '#c9a227', '#f6f1e1']],
        'citrus' => ['label' => 'Citrus fresh', 'colours' => ['#0f3d3e', '#1a7f64', '#7bd389', '#f6e05e', '#fffdf2']],
        'kente' => ['label' => 'Kente bold', 'colours' => ['#111111', '#006b3f', '#fcd116', '#ce1126', '#f5ecd7']],
    ];

    /**
     * @var array<string, array{label: string, description: string, font: string}>
     */
    public const TYPOGRAPHY = [
        'modern' => ['label' => 'Modern sans', 'description' => 'Clean, neutral and easy to read.', 'font' => 'sans'],
        'geometric' => ['label' => 'Geometric', 'description' => 'Precise and contemporary with a tech edge.', 'font' => 'display'],
        'serif' => ['label' => 'Classic serif', 'description' => 'Established, elegant and editorial.', 'font' => 'serif'],
        'rounded' => ['label' => 'Friendly rounded', 'description' => 'Soft, warm and approachable.', 'font' => 'rounded'],
        'mono' => ['label' => 'Technical mono', 'description' => 'Engineered, precise and developer-like.', 'font' => 'mono'],
        'none' => ['label' => 'No preference', 'description' => 'Let DXCOVR recommend what fits.', 'font' => 'sans'],
    ];

    /**
     * Fields every submitted questionnaire must answer (section.field).
     */
    public const REQUIRED_ON_SUBMIT = ['about.contact_name', 'about.contact_email', 'project.summary', 'final.consent'];

    /**
     * Sections that are always included. Others can be switched off per client.
     */
    public const ALWAYS_INCLUDED = ['about', 'final'];

    /**
     * @return array<string, array{title: string, intro: string, fields: array<string, array<string, mixed>>}>
     */
    public static function sections(): array
    {
        return [
            'about' => [
                'title' => 'About you',
                'intro' => 'Who we will be working with.',
                'fields' => [
                    'contact_name' => ['type' => 'text', 'label' => 'Your name', 'max' => 120],
                    'contact_email' => ['type' => 'email', 'label' => 'Email address'],
                    'contact_phone' => ['type' => 'tel', 'label' => 'Phone or WhatsApp number', 'max' => 40],
                    'role' => ['type' => 'text', 'label' => 'Your role', 'max' => 120],
                    'company' => ['type' => 'text', 'label' => 'Organisation or brand name', 'max' => 160],
                    'website' => ['type' => 'url', 'label' => 'Current website or social page', 'hint' => 'If you have one.'],
                    'about_business' => ['type' => 'textarea', 'label' => 'Tell us about your organisation', 'hint' => 'What you do, who for, and what makes you different.', 'max' => 3000],
                ],
            ],
            'project' => [
                'title' => 'Your project',
                'intro' => 'What we are building together and why.',
                'fields' => [
                    'types' => ['type' => 'checkboxes', 'label' => 'What do you need?', 'options' => [
                        'website' => 'Website', 'web_app' => 'Web application', 'mobile_app' => 'Mobile app',
                        'business_system' => 'Business system / ERP', 'ai' => 'AI solution', 'iot' => 'Hardware or IoT',
                        'immersive' => 'AR, VR or WebXR', '3d' => '3D visualisation', 'branding' => 'Branding or UI/UX design',
                        'other' => 'Something else',
                    ]],
                    'summary' => ['type' => 'textarea', 'label' => 'Describe the project in a few sentences', 'max' => 3000],
                    'problem' => ['type' => 'textarea', 'label' => 'What problem should it solve?', 'max' => 3000],
                    'goals' => ['type' => 'checkboxes', 'label' => 'Main goals', 'options' => [
                        'leads' => 'Win more customers or leads', 'sell' => 'Sell online', 'automate' => 'Automate operations',
                        'experience' => 'Improve customer experience', 'brand' => 'Build brand awareness', 'efficiency' => 'Save staff time',
                        'data' => 'Better data and reporting', 'showcase' => 'Showcase or demo to investors',
                    ]],
                    'success' => ['type' => 'textarea', 'label' => 'How will you know it has worked?', 'hint' => 'For example: bookings per week, time saved, sign-ups.', 'max' => 2000],
                ],
            ],
            'audience' => [
                'title' => 'Your audience',
                'intro' => 'Who will use it, where and how.',
                'fields' => [
                    'audience' => ['type' => 'textarea', 'label' => 'Who are your users or customers?', 'max' => 3000],
                    'regions' => ['type' => 'text', 'label' => 'Where are they?', 'hint' => 'Cities, countries or regions.', 'max' => 200],
                    'devices' => ['type' => 'checkboxes', 'label' => 'How will they mostly use it?', 'options' => [
                        'mobile' => 'Mobile phones', 'desktop' => 'Laptop or desktop', 'tablet' => 'Tablets',
                        'kiosk' => 'Kiosk or large screen', 'headset' => 'VR or AR headset', 'device' => 'A physical device we build',
                    ]],
                    'languages' => ['type' => 'text', 'label' => 'Languages needed', 'hint' => 'For example: English, Twi, French.', 'max' => 200],
                    'accessibility' => ['type' => 'textarea', 'label' => 'Any accessibility or connectivity needs?', 'hint' => 'For example: older users, low bandwidth, offline use.', 'max' => 2000],
                ],
            ],
            'look' => [
                'title' => 'Look & feel',
                'intro' => 'Pick what feels right. There are no wrong answers. This helps us understand your taste.',
                'fields' => [
                    'styles' => ['type' => 'styles', 'label' => 'Which styles feel closest to what you want?', 'hint' => 'Choose up to three.', 'max_items' => 3],
                    'mood' => ['type' => 'checkboxes', 'label' => 'Words that should describe the final product', 'hint' => 'Choose up to five.', 'max_items' => 5, 'options' => [
                        'professional' => 'Professional', 'friendly' => 'Friendly', 'innovative' => 'Innovative', 'luxurious' => 'Luxurious',
                        'playful' => 'Playful', 'calm' => 'Calm', 'energetic' => 'Energetic', 'trustworthy' => 'Trustworthy',
                        'bold' => 'Bold', 'minimal' => 'Minimal', 'warm' => 'Warm', 'technical' => 'Technical',
                        'african' => 'Proudly African', 'youthful' => 'Youthful',
                    ]],
                    'palettes' => ['type' => 'palettes', 'label' => 'Colour palettes you like', 'hint' => 'Choose up to three.', 'max_items' => 3],
                    'custom_colours' => ['type' => 'text', 'label' => 'Colours you must use', 'hint' => 'For example brand colours: “#0B6E4F”, “navy blue”, “the orange in our logo”.', 'max' => 300],
                    'avoid_colours' => ['type' => 'text', 'label' => 'Colours to avoid', 'max' => 300],
                    'typography' => ['type' => 'typography', 'label' => 'Which lettering style suits you?'],
                    'imagery' => ['type' => 'checkboxes', 'label' => 'Visual content you would like', 'options' => [
                        'photography' => 'Real photography', 'illustration' => 'Illustrations', '3d' => '3D renders',
                        'icons' => 'Icons', 'video' => 'Video and motion', 'data' => 'Charts and data visuals',
                    ]],
                    'brand_status' => ['type' => 'radio', 'label' => 'Your current branding', 'options' => [
                        'guidelines' => 'We have full brand guidelines', 'logo' => 'We have a logo only',
                        'refresh' => 'We have a brand but want a refresh', 'none' => 'Nothing yet, we need branding',
                    ]],
                ],
            ],
            'inspiration' => [
                'title' => 'Inspiration',
                'intro' => 'Show us examples. Websites, apps, products or anything that inspires you.',
                'fields' => [
                    'references' => ['type' => 'references', 'label' => 'Websites or apps you like', 'hint' => 'Add a link and tell us what you like about it: the colours, layout, feel or a feature.', 'max_items' => 8],
                    'uploads' => ['type' => 'uploads', 'label' => 'Upload screenshots, sketches, mood boards or your logo', 'hint' => 'JPG, PNG, WebP, GIF or PDF, up to 10 MB each.'],
                    'dislikes' => ['type' => 'textarea', 'label' => 'Anything you definitely do not want?', 'max' => 2000],
                    'competitors' => ['type' => 'textarea', 'label' => 'Competitors or similar organisations', 'hint' => 'Names or links, and how you want to stand apart.', 'max' => 2000],
                ],
            ],
            'features' => [
                'title' => 'Features & content',
                'intro' => 'What it needs to do and what content exists already.',
                'fields' => [
                    'features' => ['type' => 'checkboxes', 'label' => 'Features you expect', 'options' => [
                        'accounts' => 'User accounts and login', 'payments' => 'Online payments (e.g. mobile money, cards)', 'booking' => 'Bookings or appointments',
                        'dashboard' => 'Dashboards and reports', 'cms' => 'Content you can edit yourself', 'search' => 'Search',
                        'multilingual' => 'Multiple languages', 'notifications' => 'Email, SMS or WhatsApp notifications', 'chat' => 'Chat or support',
                        'maps' => 'Maps and locations', 'integrations' => 'Connections to other systems', 'offline' => 'Works offline',
                        'ai_assistant' => 'AI assistant or chatbot', 'hardware' => 'Connects to sensors or devices',
                    ]],
                    'pages' => ['type' => 'textarea', 'label' => 'Pages or screens you expect', 'hint' => 'A rough list is perfect, e.g. Home, About, Services, Contact.', 'max' => 3000],
                    'integrations' => ['type' => 'textarea', 'label' => 'Systems or tools it should connect to', 'max' => 2000],
                    'content_status' => ['type' => 'radio', 'label' => 'Is your content (text, images) ready?', 'options' => [
                        'ready' => 'Yes, it is ready', 'partial' => 'Partly ready', 'help' => 'No, we need help creating it',
                    ]],
                    'assets' => ['type' => 'checkboxes', 'label' => 'What can you provide?', 'options' => [
                        'logo' => 'Logo files', 'guidelines' => 'Brand guidelines', 'photos' => 'Photography',
                        'copy' => 'Written content', 'products' => 'Product or service data', 'videos' => 'Videos',
                    ]],
                ],
            ],
            'logistics' => [
                'title' => 'Timeline & budget',
                'intro' => 'So we can plan realistically. Ranges are fine.',
                'fields' => [
                    'timeframe' => ['type' => 'radio', 'label' => 'When do you need it?', 'options' => Enquiry::TIMEFRAMES],
                    'launch_date' => ['type' => 'date', 'label' => 'Target launch date', 'hint' => 'If there is a specific date.'],
                    'date_reason' => ['type' => 'text', 'label' => 'What is driving that date?', 'hint' => 'For example an event, a funding milestone or a season.', 'max' => 300],
                    'budget' => ['type' => 'radio', 'label' => 'Budget range', 'options' => Enquiry::BUDGET_RANGES],
                    'decision_makers' => ['type' => 'text', 'label' => 'Who else is involved in decisions?', 'max' => 300],
                    'contact_preference' => ['type' => 'checkboxes', 'label' => 'How should we keep in touch?', 'options' => [
                        'email' => 'Email', 'phone' => 'Phone calls', 'whatsapp' => 'WhatsApp', 'video' => 'Video calls', 'in_person' => 'In person',
                    ]],
                ],
            ],
            'final' => [
                'title' => 'Anything else',
                'intro' => 'Last step. Add anything we have not asked about.',
                'fields' => [
                    'notes' => ['type' => 'textarea', 'label' => 'Anything else we should know?', 'max' => 5000],
                    'consent' => ['type' => 'consent', 'label' => 'I confirm DXCOVR may use the information and files in this questionnaire to plan, quote for and deliver my project.'],
                ],
            ],
        ];
    }

    /**
     * Section keys that may be switched off per client, with their titles.
     *
     * @return array<string, string>
     */
    public static function optionalSections(): array
    {
        return collect(self::sections())
            ->except(self::ALWAYS_INCLUDED)
            ->map(fn (array $section) => $section['title'])
            ->all();
    }

    /**
     * The sections included for a client, in questionnaire order.
     *
     * @param  list<string>|null  $optional
     * @return array<string, array{title: string, intro: string, fields: array<string, array<string, mixed>>}>
     */
    public static function enabled(?array $optional): array
    {
        $keys = array_merge(self::ALWAYS_INCLUDED, $optional ?? array_keys(self::optionalSections()));

        return Arr::only(self::sections(), $keys);
    }

    /**
     * Option labels for a choice field, including the visual option sets.
     *
     * @param  array<string, mixed>  $field
     * @return array<string, string>
     */
    public static function options(array $field): array
    {
        return match ($field['type']) {
            'styles' => array_map(fn (array $style) => $style['label'], self::STYLES),
            'palettes' => array_map(fn (array $palette) => $palette['label'], self::PALETTES),
            'typography' => array_map(fn (array $type) => $type['label'], self::TYPOGRAPHY),
            default => $field['options'] ?? [],
        };
    }

    /**
     * Validation rules for answers[section][field]. Saving a draft accepts
     * partial answers; submitting also enforces REQUIRED_ON_SUBMIT.
     *
     * @param  array<string, array<string, mixed>>  $sections
     * @return array<string, mixed>
     */
    public static function rules(array $sections, bool $submitting): array
    {
        $rules = ['answers' => ['nullable', 'array']];

        foreach ($sections as $sectionKey => $section) {
            $rules["answers.{$sectionKey}"] = ['nullable', 'array'];

            foreach ($section['fields'] as $fieldKey => $field) {
                $key = "answers.{$sectionKey}.{$fieldKey}";
                $required = $submitting && in_array("{$sectionKey}.{$fieldKey}", self::REQUIRED_ON_SUBMIT, true) ? 'required' : 'nullable';

                switch ($field['type']) {
                    case 'checkboxes':
                    case 'styles':
                    case 'palettes':
                        $rules[$key] = [$required, 'array', 'max:'.($field['max_items'] ?? count(self::options($field)))];
                        $rules["{$key}.*"] = ['string', Rule::in(array_keys(self::options($field)))];
                        break;
                    case 'radio':
                    case 'typography':
                        $rules[$key] = [$required, 'string', Rule::in(array_keys(self::options($field)))];
                        break;
                    case 'references':
                        $rules[$key] = [$required, 'array', 'max:'.$field['max_items']];
                        $rules["{$key}.*.url"] = ['required', 'string', 'max:500', 'url:http,https'];
                        $rules["{$key}.*.note"] = ['nullable', 'string', 'max:500'];
                        break;
                    case 'consent':
                        $rules[$key] = $submitting ? ['accepted'] : ['nullable', 'boolean'];
                        break;
                    case 'email':
                        $rules[$key] = [$required, 'string', 'email:rfc', 'max:255'];
                        break;
                    case 'url':
                        $rules[$key] = [$required, 'string', 'max:500', 'url:http,https'];
                        break;
                    case 'date':
                        $rules[$key] = [$required, 'date'];
                        break;
                    case 'uploads':
                        break;
                    default:
                        $rules[$key] = [$required, 'string', 'max:'.($field['max'] ?? 200)];
                }
            }
        }

        return $rules;
    }

    /**
     * Readable attribute names for validation messages.
     *
     * @param  array<string, array<string, mixed>>  $sections
     * @return array<string, string>
     */
    public static function attributes(array $sections): array
    {
        $attributes = [];

        foreach ($sections as $sectionKey => $section) {
            foreach ($section['fields'] as $fieldKey => $field) {
                $label = mb_strtolower(rtrim($field['label'], '?'));
                $attributes["answers.{$sectionKey}.{$fieldKey}"] = $field['type'] === 'consent' ? 'confirmation' : $label;
                $attributes["answers.{$sectionKey}.{$fieldKey}.*.url"] = 'website link';
                $attributes["answers.{$sectionKey}.{$fieldKey}.*.note"] = 'note';
            }
        }

        return $attributes;
    }

    /**
     * Removes empty reference rows before validation so unused rows never block saving.
     *
     * @param  array<string, mixed>  $answers
     * @param  array<string, array<string, mixed>>  $sections
     * @return array<string, mixed>
     */
    public static function prepare(array $answers, array $sections): array
    {
        foreach ($sections as $sectionKey => $section) {
            foreach ($section['fields'] as $fieldKey => $field) {
                if ($field['type'] !== 'references' || ! is_array($answers[$sectionKey][$fieldKey] ?? null)) {
                    continue;
                }

                $answers[$sectionKey][$fieldKey] = collect($answers[$sectionKey][$fieldKey])
                    ->filter(fn ($row) => is_array($row) && (filled($row['url'] ?? null) || filled($row['note'] ?? null)))
                    ->values()
                    ->all();
            }
        }

        return $answers;
    }

    /**
     * Keeps only known fields of the given sections, with empty values removed.
     *
     * @param  array<string, mixed>  $validated
     * @param  array<string, array<string, mixed>>  $sections
     * @return array<string, array<string, mixed>>
     */
    public static function normalise(array $validated, array $sections): array
    {
        $clean = [];

        foreach ($sections as $sectionKey => $section) {
            foreach ($section['fields'] as $fieldKey => $field) {
                $value = $validated[$sectionKey][$fieldKey] ?? null;

                $value = match ($field['type']) {
                    'checkboxes', 'styles', 'palettes' => is_array($value) ? array_values(array_unique($value)) : [],
                    'references' => is_array($value) ? array_map(fn (array $row) => ['url' => trim((string) $row['url']), 'note' => trim((string) ($row['note'] ?? ''))], $value) : [],
                    'consent' => (bool) $value,
                    'uploads' => null,
                    default => is_string($value) ? trim($value) : null,
                };

                if ($value !== null && $value !== '' && $value !== [] && $value !== false) {
                    $clean[$sectionKey][$fieldKey] = $value;
                }
            }
        }

        return $clean;
    }

    /**
     * Whether a section has at least one answer (uploads count for Inspiration).
     *
     * @param  array<string, mixed>  $answers
     */
    public static function sectionAnswered(string $section, array $answers, int $fileCount = 0): bool
    {
        $values = collect($answers[$section] ?? [])->except('consent');

        return $values->isNotEmpty() || ($section === 'inspiration' && $fileCount > 0);
    }
}
