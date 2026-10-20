<?php

namespace App\Http\Requests\Admin;

use App\Enums\ProductStatus;
use App\Http\Requests\Admin\Concerns\HandlesStructuredInput;
use App\Http\Requests\Admin\Concerns\HasSeoRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    use HandlesStructuredInput, HasSeoRules;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->cleanRows('features', ['title', 'body']);
        $this->cleanRows('modules', ['title', 'body']);
        $this->cleanList('integrations');
        $this->cleanRows('screenshots', ['media_id', 'caption']);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_merge([
            'name' => ['required', 'string', 'max:160'],
            'slug' => $this->slugRules('products', $this->route('product')?->id),
            'tagline' => ['nullable', 'string', 'max:200'],
            'short_description' => ['required', 'string', 'max:500'],
            'body' => ['nullable', 'string', 'max:500000'],
            'audience' => ['nullable', 'string', 'max:50000'],
            'logo_media_id' => ['nullable', 'integer', 'exists:media,id'],
            'hero_media_id' => ['nullable', 'integer', 'exists:media,id'],
            'product_status' => ['required', Rule::enum(ProductStatus::class)],
            'documentation_url' => ['nullable', 'string', 'url:http,https', 'max:500'],
            'demo_requests_enabled' => ['boolean'],
            'enquiries_enabled' => ['boolean'],
            'pricing_visible' => ['boolean'],
            'pricing' => ['nullable', 'string', 'max:50000'],
            'is_featured' => ['boolean'],
            'services' => ['nullable', 'array'],
            'services.*' => ['integer', 'exists:services,id'],
            'projects' => ['nullable', 'array'],
            'projects.*' => ['integer', 'exists:projects,id'],
        ],
            $this->titleBodyRules('features', 24),
            $this->titleBodyRules('modules', 24),
            $this->stringListRules('integrations', 30, 80),
            $this->galleryRules('screenshots'),
            $this->seoRules(),
        );
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return array_merge($this->seoMessages(), $this->structuredMessages());
    }
}
