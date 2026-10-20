<?php

namespace App\Listeners;

use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;

/**
 * Writes sign-in activity to the audit log and tracks the last login.
 * Each handle* method is registered automatically through event discovery.
 */
class RecordAuthenticationEvents
{
    public function __construct(protected AuditLogger $audit, protected Request $request) {}

    public function handleLogin(Login $event): void
    {
        if ($event->user instanceof User) {
            $event->user->forceFill([
                'last_login_at' => now(),
                'last_login_ip' => $this->request->ip(),
            ])->saveQuietly();

            $this->audit->record('auth.login', $event->user, $event->user->name.' signed in');
        }
    }

    public function handleLogout(Logout $event): void
    {
        if ($event->user instanceof User) {
            $this->audit->record('auth.logout', $event->user, $event->user->name.' signed out');
        }
    }

    public function handleFailed(Failed $event): void
    {
        $this->audit->record('auth.failed', null, 'Failed sign-in attempt', [
            'email' => $event->credentials['email'] ?? null,
        ]);
    }

    public function handlePasswordReset(PasswordReset $event): void
    {
        if ($event->user instanceof User) {
            $this->audit->record('auth.password_reset', $event->user, $event->user->name.' reset their password');
        }
    }
}
