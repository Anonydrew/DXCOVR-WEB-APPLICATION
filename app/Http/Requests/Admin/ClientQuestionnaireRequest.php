<?php

namespace App\Http\Requests\Admin;

use App\Models\ClientQuestionnaire;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClientQuestionnaireRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('onboarding.manage');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'client_name' => ['required', 'string', 'max:160'],
            'client_email' => ['nullable', 'string', 'email:rfc', 'max:255'],
            'client_company' => ['nullable', 'string', 'max:160'],
            'project_name' => ['required', 'string', 'max:160'],
            'intro_message' => ['nullable', 'string', 'max:2000'],
            'sections' => ['nullable', 'array'],
            'sections.*' => ['string', Rule::in(array_keys($this->questionnaire()->optionalSections()))],
            'expires_at' => ['nullable', 'date', 'after:today'],
            'enquiry_id' => ['nullable', 'integer', 'exists:enquiries,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['expires_at' => 'link expiry date', 'client_email' => 'client email'];
    }

    /**
     * @return array<string, mixed>
     */
    public function questionnaireData(): array
    {
        $data = $this->validated();
        $data['sections'] = array_values(array_intersect(array_keys($this->questionnaire()->optionalSections()), $data['sections'] ?? []));

        return $data;
    }

    /**
     * The questionnaire being edited, or a new one (which uses the builder's current questions).
     */
    protected function questionnaire(): ClientQuestionnaire
    {
        $questionnaire = $this->route('questionnaire');

        return $questionnaire instanceof ClientQuestionnaire ? $questionnaire : new ClientQuestionnaire;
    }
}
