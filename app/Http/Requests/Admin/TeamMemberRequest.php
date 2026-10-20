<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Concerns\HandlesStructuredInput;
use App\Http\Requests\Admin\Concerns\HasSeoRules;
use App\Models\TeamMember;
use Illuminate\Foundation\Http\FormRequest;

class TeamMemberRequest extends FormRequest
{
    use HandlesStructuredInput, HasSeoRules;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->cleanRows('links', ['label', 'url']);
        $this->cleanRows('experience', ['role', 'organisation', 'period', 'summary']);
        $this->cleanList('skills');
        $this->cleanList('expertise');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $member = $this->route('member') ?? ($this->routeIs('admin.founder.update') ? TeamMember::founder() : null);

        return array_merge([
            'name' => ['required', 'string', 'max:160'],
            'slug' => $this->slugRules('team_members', is_object($member) ? $member->id : null),
            'role_title' => ['nullable', 'string', 'max:160'],
            'short_bio' => ['nullable', 'string', 'max:400'],
            'bio' => ['nullable', 'string', 'max:100000'],
            'photo_media_id' => ['nullable', 'integer', 'exists:media,id'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'is_published' => ['boolean'],
            'experience' => ['nullable', 'array', 'max:30'],
            'experience.*.role' => ['required', 'string', 'max:160'],
            'experience.*.organisation' => ['nullable', 'string', 'max:160'],
            'experience.*.period' => ['nullable', 'string', 'max:60'],
            'experience.*.summary' => ['nullable', 'string', 'max:600'],
            'projects' => ['nullable', 'array'],
            'projects.*' => ['integer', 'exists:projects,id'],
        ],
            $this->linkRules('links'),
            $this->stringListRules('skills', 40, 80),
            $this->stringListRules('expertise', 20, 120),
        );
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return array_merge($this->seoMessages(), $this->structuredMessages(), [
            'experience.*.role.required' => 'Each experience entry needs a role.',
        ]);
    }
}
