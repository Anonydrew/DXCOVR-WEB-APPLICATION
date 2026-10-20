<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\LocalAdminSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocalAdminSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'dxcovr.local_admin.email' => 'admin@dxcovr.test',
            'dxcovr.local_admin.password' => 'LocalSecret2026',
        ]);
    }

    public function test_local_environment_gets_a_super_administrator_who_can_sign_in(): void
    {
        $this->app['env'] = 'local';

        $this->seed(DatabaseSeeder::class);

        $user = User::query()->where('email', 'admin@dxcovr.test')->firstOrFail();
        $this->assertTrue($user->hasRole('Super Administrator'));
        $this->assertTrue($user->is_active);

        $this->app['env'] = 'testing';
        $this->post(route('login.store'), ['email' => 'admin@dxcovr.test', 'password' => 'LocalSecret2026'])
            ->assertRedirect();
        $this->assertAuthenticatedAs($user);
    }

    public function test_seeding_again_keeps_the_existing_account(): void
    {
        $this->app['env'] = 'local';

        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(1, User::query()->count());
    }

    public function test_no_account_is_created_outside_local(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        foreach (['production', 'staging', 'testing'] as $environment) {
            $this->app['env'] = $environment;
            $this->app->make(LocalAdminSeeder::class)->run();
        }

        $this->assertSame(0, User::query()->count());
    }

    public function test_weak_or_missing_credentials_are_skipped(): void
    {
        $this->app['env'] = 'local';

        config(['dxcovr.local_admin.password' => 'password']);
        $this->seed(DatabaseSeeder::class);

        config(['dxcovr.local_admin.password' => null]);
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(0, User::query()->count());
    }
}
