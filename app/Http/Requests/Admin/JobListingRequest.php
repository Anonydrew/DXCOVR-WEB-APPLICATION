<?php

namespace App\Http\Requests\Admin;

use App\Enums\ApplicationMethod;
use App\Enums\EmploymentType;
use App\Enums\WorkArrangement;
use App\Http\Requests\Admin\Concerns\HasSeoRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class JobListingRequest extends FormRequest
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
        return array_merge([
            'title' => ['required', 'string', 'max:200'],
            'slug' => $this->slugRules('job_listings', $this->route('job')?->id),
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'location' => ['required', 'string', 'max:160'],
            'work_arrangement' => ['required', Rule::enum(WorkArrangement::class)],
            'employment_type' => ['required', Rule::enum(EmploymentType::class)],
            'employment_type_label' => ['nullable', 'required_if:employment_type,other', 'string', 'max:80'],
            'summary' => ['required', 'string', 'max:600'],
            'description' => ['nullable', 'string', 'max:100000'],
            'responsibilities' => ['nullable', 'string', 'max:100000'],
            'requirements' => ['nullable', 'string', 'max:100000'],
            'nice_to_have' => ['nullable', 'string', 'max:100000'],
            'benefits' => ['nullable', 'string', 'max:100000'],
            'salary_range' => ['nullable', 'string', 'max:120'],
            'application_deadline' => ['nullable', 'date'],
            'auto_close' => ['boolean'],
            'application_method' => ['required', Rule::enum(ApplicationMethod::class)],
            'application_email' => ['nullable', 'required_if:application_method,email', 'email', 'max:190'],
            'application_url' => ['nullable', 'required_if:application_method,external', 'url:http,https', 'max:500'],
            'application_instructions' => ['nullable', 'string', 'max:1000'],
        ], $this->seoRules());
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return array_merge($this->seoMessages(), [
            'employment_type_label.required_if' => 'Describe the employment type when “Other” is selected.',
            'application_email.required_if' => 'An email address is required for email applications.',
            'application_url.required_if' => 'A link is required for external applications.',
        ]);
    }
}
