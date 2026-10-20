<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Concerns\HasSeoRules;
use App\Models\Page;
use App\Rules\SafeUrl;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PageRequest extends FormRequest
{
    use HasSeoRules;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $page = $this->route('page');
        $isSystem = $page instanceof Page && $page->isSystem();

        $rules = [
            'title' => ['required', 'string', 'max:200'],
            'excerpt' => ['nullable', 'string', 'max:600'],
        ];

        if ($page instanceof Page && $page->hasFeatureHero()) {
            $rules['hero_media_id'] = ['nullable', 'integer', Rule::exists('media', 'id')->where('kind', 'image')];
            $rules['hero_eyebrow'] = ['nullable', 'string', 'max:60'];
            $rules['hero_cta_label'] = ['nullable', 'string', 'max:40', 'required_with:hero_cta_url'];
            $rules['hero_cta_url'] = ['nullable', 'string', 'max:500', new SafeUrl];
        }

        if (! $isSystem) {
            $rules['slug'] = array_merge($this->slugRules('pages', $page?->id), [Rule::notIn(Page::RESERVED_SLUGS)]);
            $rules['template'] = ['required', Rule::in(array_keys(Page::TEMPLATES))];
            $rules['body'] = ['nullable', 'string', 'max:1000000'];
        }

        return array_merge($rules, $this->seoRules());
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return array_merge($this->seoMessages(), [
            'slug.not_in' => 'This address is reserved by the website. Choose another slug.',
        ]);
    }
}
