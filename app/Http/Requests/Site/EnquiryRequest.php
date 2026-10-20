<?php

namespace App\Http\Requests\Site;

use App\Enums\EnquiryType;
use App\Models\Enquiry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EnquiryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(EnquiryType::class)],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email:rfc', 'max:190'],
            'organisation' => ['nullable', 'string', 'max:160'],
            'phone' => ['nullable', 'string', 'max:40', 'regex:/^[0-9+()\s.\-]{6,40}$/'],
            'service_id' => ['nullable', 'integer', Rule::exists('services', 'id')->where('status', 'published')],
            'product_id' => [
                Rule::requiredIf(in_array($this->input('type'), [EnquiryType::Demo->value, EnquiryType::Product->value], true)),
                'nullable', 'integer', Rule::exists('products', 'id')->where('status', 'published'),
            ],
            'project_type' => ['nullable', Rule::in(array_keys(Enquiry::PROJECT_TYPES))],
            'budget_range' => ['nullable', Rule::in(array_keys(Enquiry::BUDGET_RANGES))],
            'timeframe' => ['nullable', Rule::in(array_keys(Enquiry::TIMEFRAMES))],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
            'consent' => ['accepted'],
            'source_url' => ['nullable', 'string', 'max:500'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('type')) {
            $this->merge(['type' => EnquiryType::Project->value]);
        }
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'service_id' => 'service',
            'product_id' => 'product',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'consent.accepted' => 'Please confirm you have read the privacy notice.',
            'message.min' => 'Please tell us a little more (at least :min characters).',
            'phone.regex' => 'Please enter a valid phone number.',
        ];
    }

    /**
     * The submitting page, kept only when it belongs to this site.
     */
    public function safeSourceUrl(): ?string
    {
        $url = (string) $this->input('source_url');

        return str_starts_with($url, rtrim((string) config('app.url'), '/')) ? $url : null;
    }
}
