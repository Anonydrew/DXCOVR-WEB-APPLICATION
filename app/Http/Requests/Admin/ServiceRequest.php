<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Concerns\HandlesStructuredInput;
use App\Http\Requests\Admin\Concerns\HasSeoRules;
use App\Models\Service;
use App\Rules\SafeUrl;
use App\Rules\SupportedVideo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ServiceRequest extends FormRequest
{
    use HandlesStructuredInput, HasSeoRules;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->cleanList('capabilities');
        $this->cleanRows('use_cases', ['title', 'body']);
        $this->cleanRows('process_steps', ['title', 'body']);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_merge([
            'title' => ['required', 'string', 'max:200'],
            'slug' => $this->slugRules('services', $this->route('service')?->id),
            'icon' => ['required', Rule::in(array_keys(Service::ICONS))],
            'short_description' => ['required', 'string', 'max:400'],
            'body' => ['nullable', 'string', 'max:500000'],
            'hero_media_id' => ['nullable', 'integer', 'exists:media,id'],
            'hero_video_url' => ['nullable', 'string', 'max:500', new SupportedVideo],
            'cta_heading' => ['nullable', 'string', 'max:160'],
            'cta_body' => ['nullable', 'string', 'max:500'],
            'cta_label' => ['nullable', 'string', 'max:60'],
            'cta_url' => ['nullable', 'string', 'max:500', new SafeUrl],
            'is_featured' => ['boolean'],
        ],
            $this->stringListRules('capabilities', 20),
            $this->titleBodyRules('use_cases'),
            $this->titleBodyRules('process_steps', 8),
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
