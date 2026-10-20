<?php

namespace App\Http\Controllers\Admin;

use App\Models\Media;
use App\Rules\RasterImage;
use App\Rules\SafeUrl;
use App\Services\SiteSettings;
use App\Support\ContentCache;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Grouped site settings. Each group declares its fields, validation and the
 * permission required to change it.
 */
class SettingsController extends AdminController
{
    public const SOCIAL_NETWORKS = [
        'linkedin' => 'LinkedIn',
        'x' => 'X (Twitter)',
        'instagram' => 'Instagram',
        'facebook' => 'Facebook',
        'youtube' => 'YouTube',
        'github' => 'GitHub',
        'behance' => 'Behance',
        'dribbble' => 'Dribbble',
        'link' => 'Other',
    ];

    public function __construct(protected SiteSettings $settings) {}

    /**
     * @return array<string, array{label: string, description: string, permission: string, fields: array<string, array<string, mixed>>}>
     */
    public static function groups(): array
    {
        return [
            'general' => [
                'label' => 'General',
                'description' => 'Site identity and footer content.',
                'permission' => 'settings.manage',
                'fields' => [
                    'site.name' => ['type' => 'text', 'label' => 'Site name', 'rules' => ['required', 'string', 'max:60']],
                    'site.tagline' => ['type' => 'text', 'label' => 'Tagline', 'rules' => ['nullable', 'string', 'max:120']],
                    'site.description' => ['type' => 'textarea', 'label' => 'Positioning statement', 'rules' => ['nullable', 'string', 'max:500'], 'hint' => 'Used in structured data and as a fallback description.'],
                    'footer.statement' => ['type' => 'textarea', 'label' => 'Footer statement', 'rules' => ['nullable', 'string', 'max:300']],
                    'footer.cta_heading' => ['type' => 'text', 'label' => 'Footer call-to-action heading', 'rules' => ['nullable', 'string', 'max:120']],
                    'footer.cta_label' => ['type' => 'text', 'label' => 'Footer button label', 'rules' => ['nullable', 'string', 'max:40']],
                    'footer.cta_url' => ['type' => 'text', 'label' => 'Footer button destination', 'rules' => ['nullable', 'string', 'max:500', new SafeUrl]],
                    'footer.copyright' => ['type' => 'text', 'label' => 'Copyright line', 'rules' => ['nullable', 'string', 'max:120'], 'hint' => 'The current year is added automatically.'],
                ],
            ],
            'contact' => [
                'label' => 'Contact & social',
                'description' => 'Details shown on the contact page and footer. Leave a field empty to hide it.',
                'permission' => 'settings.manage',
                'fields' => [
                    'contact.email' => ['type' => 'email', 'label' => 'Public email address', 'rules' => ['nullable', 'email', 'max:190']],
                    'contact.phone' => ['type' => 'text', 'label' => 'Phone number', 'rules' => ['nullable', 'string', 'max:40', 'regex:/^[0-9+()\s.\-]{6,40}$/']],
                    'contact.address' => ['type' => 'textarea', 'label' => 'Address', 'rules' => ['nullable', 'string', 'max:300']],
                    'contact.location_summary' => ['type' => 'text', 'label' => 'Location summary', 'rules' => ['nullable', 'string', 'max:160'], 'hint' => 'Shown when no address is set.'],
                    'contact.hours' => ['type' => 'text', 'label' => 'Office hours', 'rules' => ['nullable', 'string', 'max:160']],
                    'contact.whatsapp_number' => ['type' => 'text', 'label' => 'WhatsApp number', 'rules' => ['nullable', 'string', 'max:30', 'regex:/^\+?[0-9\s]{7,20}$/'], 'hint' => 'International format, e.g. +233 …. Leave empty to hide WhatsApp links.'],
                    'contact.whatsapp_message' => ['type' => 'text', 'label' => 'WhatsApp greeting', 'rules' => ['nullable', 'string', 'max:200']],
                    'privacy.enquiry_notice' => ['type' => 'textarea', 'label' => 'Enquiry form privacy notice', 'rules' => ['nullable', 'string', 'max:600'], 'hint' => 'Shown next to the consent checkbox on enquiry forms.'],
                    'social.links' => ['type' => 'social', 'label' => 'Social profiles', 'rules' => ['nullable', 'array', 'max:12']],
                ],
            ],
            'notifications' => [
                'label' => 'Notifications',
                'description' => 'Who receives emails when people get in touch or apply.',
                'permission' => 'settings.manage',
                'fields' => [
                    'notifications.enquiry_recipients' => ['type' => 'emails', 'label' => 'Enquiry notification recipients', 'rules' => ['nullable', 'string', 'max:1000'], 'hint' => 'One email address per line.'],
                    'notifications.application_recipients' => ['type' => 'emails', 'label' => 'Job application notification recipients', 'rules' => ['nullable', 'string', 'max:1000'], 'hint' => 'One email address per line. Notifications never include the CV.'],
                    'notifications.onboarding_recipients' => ['type' => 'emails', 'label' => 'Onboarding questionnaire recipients', 'rules' => ['nullable', 'string', 'max:1000'], 'hint' => 'One email address per line. Leave empty to use the enquiry recipients.'],
                    'notifications.enquiry_acknowledgement' => ['type' => 'toggle', 'label' => 'Send an acknowledgement email to people who submit an enquiry'],
                    'notifications.application_acknowledgement' => ['type' => 'toggle', 'label' => 'Send an acknowledgement email to applicants'],
                    'editorial.review_enabled' => ['type' => 'toggle', 'label' => 'Editorial review', 'hint' => 'Authors submit articles for review before an editor publishes them.'],
                    'notifications.editorial_review' => ['type' => 'toggle', 'label' => 'Email editors when an article is submitted for review'],
                ],
            ],
            'seo' => [
                'label' => 'SEO',
                'description' => 'Defaults used when a page has no specific metadata.',
                'permission' => 'seo.manage',
                'fields' => [
                    'seo.title_template' => ['type' => 'text', 'label' => 'Title template', 'rules' => ['required', 'string', 'max:80', 'regex:/%s/'], 'hint' => 'Use %s for the page title, e.g. “%s | DXCOVR”.'],
                    'seo.default_description' => ['type' => 'textarea', 'label' => 'Default meta description', 'rules' => ['nullable', 'string', 'max:320']],
                    'seo.default_og_image_id' => ['type' => 'media', 'label' => 'Default social preview image', 'rules' => ['nullable', 'integer', new RasterImage], 'hint' => '1200 × 630 pixels recommended.'],
                    'seo.organization_legal_name' => ['type' => 'text', 'label' => 'Organisation legal name', 'rules' => ['nullable', 'string', 'max:160'], 'hint' => 'Used in structured data for search engines.'],
                    'seo.organization_logo_id' => ['type' => 'media', 'label' => 'Organisation logo', 'rules' => ['nullable', 'integer', new RasterImage], 'hint' => 'PNG or JPG. Search engines do not accept SVG here.'],
                    'seo.discourage_indexing' => ['type' => 'toggle', 'label' => 'Discourage search engines from indexing the whole site', 'hint' => 'Use for staging sites or before launch. Adds noindex to every page and blocks crawlers in robots.txt.'],
                    'seo.robots_extra' => ['type' => 'textarea', 'label' => 'Additional robots.txt rules', 'rules' => ['nullable', 'string', 'max:2000'], 'hint' => 'One Allow, Disallow or Crawl-delay rule per line. Other lines are ignored.'],
                ],
            ],
            'careers' => [
                'label' => 'Careers',
                'description' => 'Recruitment privacy and data retention.',
                'permission' => 'careers.manage',
                'fields' => [
                    'careers.privacy_notice' => ['type' => 'textarea', 'label' => 'Recruitment privacy notice', 'rules' => ['required', 'string', 'max:1000'], 'hint' => 'Applicants must accept this before applying.'],
                    'careers.retention_days' => ['type' => 'number', 'label' => 'Retention period (days)', 'rules' => ['required', 'integer', 'min:30', 'max:3650'], 'hint' => 'Applications and CVs are deleted automatically this many days after a final decision.'],
                    'careers.general_applications_email' => ['type' => 'email', 'label' => 'Speculative applications email', 'rules' => ['nullable', 'email', 'max:190'], 'hint' => 'Shown on the Careers page when there are no open roles.'],
                ],
            ],
            'newsletter' => [
                'label' => 'Newsletter',
                'description' => 'Double opt-in newsletter sign-up.',
                'permission' => 'settings.manage',
                'fields' => [
                    'newsletter.enabled' => ['type' => 'toggle', 'label' => 'Show newsletter sign-up forms', 'hint' => 'Subscribers must confirm by email. Export the list from Inbox → Newsletter.'],
                    'newsletter.heading' => ['type' => 'text', 'label' => 'Heading', 'rules' => ['nullable', 'string', 'max:120']],
                    'newsletter.body' => ['type' => 'textarea', 'label' => 'Description', 'rules' => ['nullable', 'string', 'max:400']],
                ],
            ],
        ];
    }

    public function edit(Request $request, string $group): View
    {
        $definition = $this->authorizeGroup($request, $group);

        $values = [];
        foreach ($definition['fields'] as $key => $field) {
            $values[$key] = $this->settings->get($key);
        }

        $mediaIds = collect($definition['fields'])->filter(fn ($field) => $field['type'] === 'media')->keys()->map(fn ($key) => $values[$key])->filter();

        return view('admin.settings.edit', [
            'group' => $group,
            'definition' => $definition,
            'values' => $values,
            'media' => $mediaIds->isEmpty() ? collect() : Media::query()->whereIn('id', $mediaIds)->get()->keyBy('id'),
            'groups' => collect(self::groups())->filter(fn ($item) => $request->user()->can($item['permission']))->map(fn ($item) => $item['label']),
            'networks' => self::SOCIAL_NETWORKS,
        ]);
    }

    public function update(Request $request, string $group): RedirectResponse
    {
        $definition = $this->authorizeGroup($request, $group);

        $rules = [];
        foreach ($definition['fields'] as $key => $field) {
            $input = $this->inputKey($key);

            $rules[$input] = match ($field['type']) {
                'toggle' => ['boolean'],
                default => $field['rules'] ?? ['nullable', 'string', 'max:500'],
            };

            if ($field['type'] === 'social') {
                $rules[$input.'.*.network'] = ['required', Rule::in(array_keys(self::SOCIAL_NETWORKS))];
                $rules[$input.'.*.url'] = ['required', 'string', 'max:500', new SafeUrl(false)];
                $rules[$input.'.*.label'] = ['nullable', 'string', 'max:60'];
            }
        }

        $this->cleanSocialRows($request, $definition);
        $validated = $request->validate($rules, [
            'settings.*.regex' => 'Please check the format of this field.',
            'settings.seo-title_template.regex' => 'The title template must contain %s.',
        ]);

        $values = [];
        foreach ($definition['fields'] as $key => $field) {
            $value = data_get($validated, $this->inputKey($key));

            $values[$key] = match ($field['type']) {
                'toggle' => $request->boolean($this->inputKey($key)),
                'number', 'media' => $value === null || $value === '' ? null : (int) $value,
                'emails' => $this->parseEmails((string) $value),
                'social' => array_values($value ?? []),
                default => is_string($value) ? trim($value) : $value,
            };
        }

        foreach ($definition['fields'] as $key => $field) {
            if ($field['type'] === 'emails' && $values[$key] === false) {
                return back()->withInput()->withErrors([$this->inputKey($key) => 'One or more email addresses are not valid.']);
            }
        }

        $this->settings->set($values);
        ContentCache::flush();

        $this->audit()->record('settings.updated', null, 'Updated '.$definition['label'].' settings', ['keys' => array_keys($values)]);

        return redirect()->route('admin.settings.edit', $group)->with('status', $definition['label'].' settings saved.');
    }

    /**
     * @return array{label: string, description: string, permission: string, fields: array<string, array<string, mixed>>}
     */
    protected function authorizeGroup(Request $request, string $group): array
    {
        $definition = self::groups()[$group] ?? null;
        abort_if($definition === null, 404);
        abort_unless($request->user()->can($definition['permission']), 403);

        return $definition;
    }

    public static function inputKey(string $key): string
    {
        return 'settings.'.str_replace('.', '-', $key);
    }

    /**
     * @return list<string>|false
     */
    protected function parseEmails(string $value): array|false
    {
        $emails = collect(preg_split('/[\s,;]+/', $value) ?: [])->map(fn ($email) => trim($email))->filter()->unique()->values();

        if ($emails->contains(fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL) === false)) {
            return false;
        }

        return $emails->all();
    }

    /**
     * @param  array{fields: array<string, array<string, mixed>>}  $definition
     */
    protected function cleanSocialRows(Request $request, array $definition): void
    {
        foreach ($definition['fields'] as $key => $field) {
            if ($field['type'] !== 'social') {
                continue;
            }

            $input = $this->inputKey($key);
            $rows = collect($request->input($input, []))
                ->filter(fn ($row) => is_array($row) && filled($row['url'] ?? null))
                ->map(fn ($row) => ['network' => $row['network'] ?? 'link', 'url' => trim((string) $row['url']), 'label' => $row['label'] ?? null])
                ->values()
                ->all();

            $request->merge(['settings' => array_merge((array) $request->input('settings', []), [str_replace('settings.', '', $input) => $rows])]);
        }
    }
}
