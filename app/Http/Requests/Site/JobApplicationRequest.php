<?php

namespace App\Http\Requests\Site;

use App\Rules\ReadableUpload;
use App\Rules\SafeDocument;
use Illuminate\Foundation\Http\FormRequest;

class JobApplicationRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email:rfc', 'max:190'],
            'phone' => ['nullable', 'string', 'max:40', 'regex:/^[0-9+()\s.\-]{6,40}$/'],
            'cover_letter' => ['required', 'string', 'min:40', 'max:6000'],
            'portfolio_url' => ['nullable', 'string', 'url:http,https', 'max:500'],
            'cv' => ['bail', 'required', 'file', new ReadableUpload, new SafeDocument(config('dxcovr.careers.cv_extensions'), (int) config('dxcovr.careers.cv_max_kb'))],
            'consent' => ['accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'cv' => 'CV',
            'cover_letter' => 'cover letter',
            'portfolio_url' => 'portfolio or profile link',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'consent.accepted' => 'Please confirm you have read the recruitment privacy notice.',
            'cover_letter.min' => 'Please tell us a little more about why you are interested in this role (at least :min characters).',
            'phone.regex' => 'Please enter a valid phone number.',
        ];
    }
}
