<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\AccountInvitation;
use App\Support\Permissions;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use PragmaRX\Google2FA\Google2FA;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        $this->syncPermissions();
    }

    public function test_administrator_invites_a_user_who_sets_their_own_password(): void
    {
        $this->actingAsRole('Administrator');

        $this->post('/admin/users', ['name' => 'New Editor', 'email' => 'New.Editor@Example.com', 'roles' => ['Editor']])
            ->assertRedirect(route('admin.users.index'));

        $user = User::query()->where('email', 'new.editor@example.com')->firstOrFail();
        $this->assertTrue($user->hasRole('Editor'));
        $this->assertTrue($user->is_active);
        Notification::assertSentTo($user, AccountInvitation::class);
        $this->assertDatabaseHas('audit_logs', ['action' => 'user.created', 'auditable_id' => $user->id]);
    }

    public function test_only_super_admins_can_grant_or_manage_super_admin(): void
    {
        $super = User::factory()->superAdmin()->create();
        $this->actingAsRole('Administrator');

        $this->post('/admin/users', ['name' => 'Sneaky', 'email' => 'sneaky@example.com', 'roles' => [Permissions::SUPER_ADMIN_ROLE]])
            ->assertSessionHasErrors('roles.0');

        $this->get(route('admin.users.edit', $super))->assertForbidden();
        $this->post(route('admin.users.toggle-active', $super))->assertForbidden();
        $this->delete(route('admin.users.destroy', $super))->assertForbidden();
    }

    public function test_last_active_super_admin_is_protected(): void
    {
        $me = $this->actingAsRole(Permissions::SUPER_ADMIN_ROLE);

        $this->put(route('admin.users.update', $me), ['name' => $me->name, 'email' => $me->email, 'roles' => ['Editor']])
            ->assertSessionHas('error');
        $this->assertTrue($me->fresh()->isSuperAdmin());

        $this->post(route('admin.users.toggle-active', $me))->assertSessionHas('error');
        $this->delete(route('admin.users.destroy', $me))->assertSessionHas('error');
    }

    public function test_deactivating_a_user_blocks_them(): void
    {
        $this->actingAsRole(Permissions::SUPER_ADMIN_ROLE);
        $editor = User::factory()->withRole('Editor')->create();

        $this->post(route('admin.users.toggle-active', $editor))->assertSessionHas('status');
        $this->assertFalse($editor->fresh()->is_active);

        $this->actingAs($editor->fresh());
        $this->get('/admin')->assertRedirect(route('login'));
    }

    public function test_custom_roles_can_be_created_with_selected_permissions(): void
    {
        $this->actingAsRole(Permissions::SUPER_ADMIN_ROLE);

        $this->post('/admin/roles', ['name' => 'Media Manager', 'permissions' => ['dashboard.view', 'media.view', 'media.upload', 'not.a.permission']])
            ->assertSessionHasErrors('permissions.3');

        $this->post('/admin/roles', ['name' => 'Media Manager', 'permissions' => ['dashboard.view', 'media.view', 'media.upload']])
            ->assertRedirect(route('admin.roles.index'));

        $user = User::factory()->withRole('Media Manager')->create();
        $this->actingAs($user);

        $this->get('/admin/media')->assertOk();
        $this->get('/admin/articles')->assertForbidden();
    }

    public function test_roles_in_use_cannot_be_deleted(): void
    {
        $this->actingAsRole(Permissions::SUPER_ADMIN_ROLE);
        User::factory()->withRole('Editor')->create();

        $this->delete(route('admin.roles.destroy', Role::findByName('Editor')))->assertSessionHas('error');
        $this->assertNotNull(Role::findByName('Editor'));
    }

    public function test_provisioning_command_creates_admin_without_default_password(): void
    {
        $this->artisan('dxcovr:create-admin', ['--name' => 'Anthony', '--email' => 'founder@example.com', '--send-reset-link' => true])
            ->assertSuccessful();

        $user = User::query()->where('email', 'founder@example.com')->firstOrFail();
        $this->assertTrue($user->isSuperAdmin());
        $this->assertFalse(Hash::check('password', $user->password));
        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_provisioning_command_enforces_password_policy(): void
    {
        $this->artisan('dxcovr:create-admin', ['--name' => 'Weak', '--email' => 'weak@example.com'])
            ->expectsQuestion('Password (min. 12 characters, mixed case and numbers)', 'short')
            ->assertFailed();

        $this->assertDatabaseMissing('users', ['email' => 'weak@example.com']);

        $this->artisan('dxcovr:create-admin', ['--name' => 'Strong', '--email' => 'strong@example.com'])
            ->expectsQuestion('Password (min. 12 characters, mixed case and numbers)', 'Correct-Horse-Battery-9')
            ->assertSuccessful();

        $this->assertTrue(User::query()->where('email', 'strong@example.com')->firstOrFail()->isSuperAdmin());
    }

    public function test_password_reset_flow(): void
    {
        $user = User::factory()->withRole('Editor')->create(['email' => 'reset@example.com']);

        $this->post('/admin/forgot-password', ['email' => 'reset@example.com'])->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user) {
            $this->post('/admin/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'New-Secure-Password-1',
                'password_confirmation' => 'New-Secure-Password-1',
            ])->assertSessionHasNoErrors();

            return Hash::check('New-Secure-Password-1', $user->fresh()->password);
        });
    }

    public function test_weak_passwords_are_rejected_on_reset(): void
    {
        $user = User::factory()->create(['email' => 'weak-reset@example.com']);
        $token = Password::broker()->createToken($user);

        $this->post('/admin/reset-password', ['token' => $token, 'email' => $user->email, 'password' => 'password', 'password_confirmation' => 'password'])
            ->assertSessionHasErrors('password');
    }

    public function test_user_can_enable_two_factor_authentication(): void
    {
        $user = $this->actingAsRole('Editor');

        $this->withSession(['auth.password_confirmed_at' => time()])
            ->post(route('two-factor.enable'))
            ->assertRedirect();

        $user->refresh();
        $this->assertNotNull($user->two_factor_secret);
        $this->assertNull($user->two_factor_confirmed_at);

        $this->get('/admin/account')->assertOk()->assertSee('Scan this QR code');

        $code = app(Google2FA::class)->getCurrentOtp(decrypt($user->two_factor_secret));
        $this->withSession(['auth.password_confirmed_at' => time()])
            ->post(route('two-factor.confirm'), ['code' => $code])
            ->assertRedirect();

        $this->assertTrue($user->fresh()->hasTwoFactorEnabled());
    }

    public function test_two_factor_users_must_pass_the_challenge(): void
    {
        $user = User::factory()->withRole('Editor')->create(['email' => 'tfa@example.com']);
        $secret = app(Google2FA::class)->generateSecretKey();
        $user->forceFill([
            'two_factor_secret' => encrypt($secret),
            'two_factor_recovery_codes' => encrypt(json_encode(['recovery-code-1'])),
            'two_factor_confirmed_at' => now(),
        ])->save();

        $this->post('/admin/login', ['email' => 'tfa@example.com', 'password' => 'password'])
            ->assertRedirect(route('two-factor.login'));
        $this->assertGuest();

        $this->post(route('two-factor.login.store'), ['code' => '000000'])->assertSessionHasErrors('code');
        $this->assertGuest();

        $this->post(route('two-factor.login.store'), ['code' => app(Google2FA::class)->getCurrentOtp($secret)])
            ->assertRedirect('/admin');
        $this->assertAuthenticatedAs($user);
    }

    public function test_profile_can_be_updated(): void
    {
        $user = $this->actingAsRole('Author');

        $this->put(route('user-profile-information.update'), ['name' => 'Updated Name', 'email' => $user->email, 'job_title' => 'Writer', 'bio' => 'Writes about AI.'])
            ->assertSessionHasNoErrors();

        $this->assertSame('Writer', $user->fresh()->job_title);
    }
}
