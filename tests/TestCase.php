<?php

namespace Tests;

use App\Models\User;
use App\Services\SiteSettings;
use App\Services\Spam\SpamGuard;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Crypt;

abstract class TestCase extends BaseTestCase
{
    /**
     * Create a user with the given role and sign them in.
     */
    protected function actingAsRole(string $role): User
    {
        $user = User::factory()->withRole($role)->create();
        $this->actingAs($user);

        return $user;
    }

    protected function syncPermissions(): void
    {
        Artisan::call('dxcovr:sync-permissions');
    }

    /**
     * Valid spam-protection fields: an empty honeypot and a start time older than the minimum.
     *
     * @return array<string, string>
     */
    protected function spamFields(): array
    {
        return [
            SpamGuard::HONEYPOT_FIELD => '',
            SpamGuard::TIMESTAMP_FIELD => Crypt::encryptString((string) now()->subMinute()->getTimestamp()),
        ];
    }

    /**
     * @param  array<string, mixed>  $values
     */
    protected function setting(array $values): void
    {
        app(SiteSettings::class)->set($values);
    }
}
