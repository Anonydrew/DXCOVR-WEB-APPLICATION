<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\Permissions;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Password as PasswordBroker;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

/**
 * Secure provisioning of administrator accounts. There is no default
 * password: the operator types one (hidden input) or the user receives a
 * one-time password-reset link by email.
 */
#[Signature('dxcovr:create-admin
    {--name= : Full name}
    {--email= : Email address used to sign in}
    {--role=Super Administrator : Role to assign}
    {--send-reset-link : Email a password set-up link instead of typing a password}')]
#[Description('Create an administrator account (used for the initial Super Administrator)')]
class CreateAdministrator extends Command
{
    public function handle(): int
    {
        $this->call('dxcovr:sync-permissions');

        $name = $this->option('name') ?: text('Full name', required: true);
        $email = Str::lower((string) ($this->option('email') ?: text('Email address', required: true)));
        $roleName = (string) $this->option('role');

        if (! Role::query()->where('name', $roleName)->exists()) {
            $this->components->error("The role “{$roleName}” does not exist.");

            return self::FAILURE;
        }

        $sendLink = (bool) $this->option('send-reset-link');
        $plainPassword = $sendLink ? Str::password(40) : password('Password (min. 12 characters, mixed case and numbers)', required: true);

        $validator = Validator::make(
            ['name' => $name, 'email' => $email, 'password' => $plainPassword],
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', 'unique:users,email'],
                'password' => ['required', Password::defaults()],
            ],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->components->error($error);
            }

            return self::FAILURE;
        }

        $user = new User;
        $user->forceFill([
            'name' => $name,
            'email' => $email,
            'password' => $plainPassword,
            'is_active' => true,
            'email_verified_at' => now(),
        ])->save();

        $user->assignRole($roleName);

        if ($sendLink) {
            $status = PasswordBroker::broker()->sendResetLink(['email' => $email]);
            $status === PasswordBroker::RESET_LINK_SENT
                ? $this->components->info('A password set-up link has been emailed to '.$email.'.')
                : $this->components->warn('The account was created but the email could not be sent ('.$status.'). Use “Forgot password” on the sign-in page once mail is configured.');
        }

        $this->components->info("Created {$roleName} account for {$email}. Sign in at ".route('login'));

        if ($roleName === Permissions::SUPER_ADMIN_ROLE) {
            $this->components->warn('Enable two-factor authentication from Account → Security after your first sign-in.');
        }

        return self::SUCCESS;
    }
}
