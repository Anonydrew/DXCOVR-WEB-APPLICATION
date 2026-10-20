<?php

namespace App\Http\Requests\Admin;

use App\Enums\DeliveryStatus;
use App\Enums\ProjectType;
use App\Http\Requests\Admin\Concerns\HandlesStructuredInput;
use App\Http\Requests\Admin\Concerns\HasSeoRules;
use App\Rules\SupportedVideo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProjectRequest extends FormRequest
{
    use HandlesStructuredInput, HasSeoRules;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->cleanRows('features', ['title', 'body']);
        $this->cleanList('video_urls');
        $this->cleanRows('gallery', ['media_id', 'caption']);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_merge([
            'title' => ['required', 'string', 'max:200'],
            'slug' => $this->slugRules('projects', $this->route('project')?->id),
            'summary' => ['required', 'string', 'max:600'],
            'tagline' => ['nullable', 'string', 'max:90'],
            'project_category_id' => ['nullable', 'integer', 'exists:project_categories,id'],
            'project_type' => ['required', Rule::enum(ProjectType::class)],
            'delivery_status' => ['nullable', Rule::enum(DeliveryStatus::class)],
            'client_name' => ['nullable', 'string', 'max:160'],
            'is_confidential' => ['boolean'],
            'anonymised_client_label' => ['nullable', 'string', 'max:160'],
            'duration' => ['nullable', 'string', 'max:80'],
            'year' => ['nullable', 'integer', 'min:1990', 'max:'.(now()->year + 1)],
            'cover_media_id' => ['nullable', 'integer', 'exists:media,id'],
            'challenge' => ['nullable', 'string', 'max:200000'],
            'objectives' => ['nullable', 'string', 'max:200000'],
            'approach' => ['nullable', 'string', 'max:200000'],
            'solution' => ['nullable', 'string', 'max:200000'],
            'role' => ['nullable', 'string', 'max:200000'],
            'results' => ['nullable', 'string', 'max:200000'],
            'external_url' => ['nullable', 'string', 'url:http,https', 'max:500'],
            'is_featured' => ['boolean'],
            'services' => ['nullable', 'array'],
            'services.*' => ['integer', 'exists:services,id'],
            'products' => ['nullable', 'array'],
            'products.*' => ['integer', 'exists:products,id'],
            'team_members' => ['nullable', 'array'],
            'team_members.*' => ['integer', 'exists:team_members,id'],
            'technologies' => ['nullable', 'string', 'max:600'],
            'video_urls' => ['nullable', 'array', 'max:6'],
            'video_urls.*' => ['string', 'max:500', new SupportedVideo],
        ],
            $this->titleBodyRules('features', 18),
            $this->galleryRules('gallery'),
            $this->seoRules(),
        );
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return array_merge($this->seoMessages(), $this->structuredMessages(), [
            'results.max' => 'The results section is too long.',
        ]);
    }
}
