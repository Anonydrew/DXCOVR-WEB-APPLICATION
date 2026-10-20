<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\Permissions;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

/**
 * Creates a Super Administrator for local development from LOCAL_ADMIN_EMAIL
 * and LOCAL_ADMIN_PASSWORD in .env. Does nothing outside APP_ENV=local, when
 * either value is missing, or when the account already exists.
 */
class LocalAdminSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->isLocal()) {
            return;
        }

        $email = Str::lower((string) config('dxcovr.local_admin.email'));
        $password = (string) config('dxcovr.local_admin.password');

        if ($email === '' || $password === '') {
            $this->command?->warn('Local admin skipped: set LOCAL_ADMIN_EMAIL and LOCAL_ADMIN_PASSWORD in .env.');

            return;
        }

        if (User::query()->where('email', $email)->exists()) {
            return;
        }

        $validator = Validator::make(['email' => $email, 'password' => $password], [
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', Password::defaults()],
        ]);

        if ($validator->fails()) {
            $this->command?->error('Local admin skipped: '.implode(' ', $validator->errors()->all()));

            return;
        }

        $user = new User;
        $user->forceFill([
            'name' => (string) config('dxcovr.local_admin.name'),
            'email' => $email,
            'password' => $password,
            'is_active' => true,
            'email_verified_at' => now(),
        ])->save();

        $user->assignRole(Permissions::SUPER_ADMIN_ROLE);

        $this->command?->info("Local admin ready: {$email}");
    }
}
