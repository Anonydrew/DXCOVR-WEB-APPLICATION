<?php

namespace App\Actions\Fortify;

use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;

class UpdateUserProfileInformation implements UpdatesUserProfileInformation
{
    public function __construct(protected AuditLogger $audit) {}

    /**
     * Validate and update the given user's profile information.
     *
     * @param  array<string, string>  $input
     *
     * @throws ValidationException
     */
    public function update(User $user, array $input): void
    {
        $validated = Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'job_title' => ['nullable', 'string', 'max:120'],
            'bio' => ['nullable', 'string', 'max:1000'],
        ])->validateWithBag('updateProfileInformation');

        $user->forceFill([
            'name' => $validated['name'],
            'email' => Str::lower($validated['email']),
            'job_title' => $validated['job_title'] ?? null,
            'bio' => $validated['bio'] ?? null,
        ])->save();

        $this->audit->record('user.profile_updated', $user, $user->name.' updated their profile', $this->audit->changes($user));
    }
}
