<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Notifications\AccountInvitation;
use App\Support\Permissions;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;
use Throwable;

/**
 * Administrator accounts. New users choose their own password through an
 * emailed link, so no password is ever set or seen by another person.
 * Only Super Administrators can grant or manage the Super Administrator role,
 * and the last active Super Administrator cannot be removed or deactivated.
 */
class UserController extends AdminController
{
    public function index(Request $request): View
    {
        return view('admin.users.index', [
            'users' => User::query()->with('roles')
                ->when($request->query('q'), fn ($query, $term) => $query->where(fn ($query) => $query->where('name', 'like', '%'.addcslashes((string) $term, '%_').'%')->orWhere('email', 'like', '%'.addcslashes((string) $term, '%_').'%')))
                ->orderBy('name')
                ->paginate(30)
                ->withQueryString(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.users.form', ['user' => new User, 'roles' => $this->assignableRoles($request->user())]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'job_title' => ['nullable', 'string', 'max:120'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => [Rule::in($this->assignableRoles($request->user())->keys()->all())],
        ]);

        $user = DB::transaction(function () use ($data): User {
            $user = new User;
            $user->forceFill([
                'name' => $data['name'],
                'email' => Str::lower($data['email']),
                'job_title' => $data['job_title'] ?? null,
                'password' => Str::password(48),
                'is_active' => true,
                'email_verified_at' => now(),
            ])->save();

            $user->syncRoles($data['roles']);

            return $user;
        });

        $this->audit()->record('user.created', $user, 'Created account for '.$user->email, ['roles' => $data['roles']]);

        $sent = $this->sendInvitation($user);

        return redirect()->route('admin.users.index')->with(
            $sent ? 'status' : 'warning',
            $sent ? 'Account created. An email invitation to set a password has been sent to '.$user->email.'.' : 'Account created, but the invitation email could not be sent. Check the mail settings, then use “Send password link”.',
        );
    }

    public function edit(Request $request, User $user): View
    {
        $this->guardSuperAdmin($request->user(), $user);
        $user->load('roles');

        return view('admin.users.form', ['user' => $user, 'roles' => $this->assignableRoles($request->user())]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->guardSuperAdmin($request->user(), $user);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'job_title' => ['nullable', 'string', 'max:120'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => [Rule::in($this->assignableRoles($request->user())->keys()->all())],
        ]);

        if ($user->isSuperAdmin() && ! in_array(Permissions::SUPER_ADMIN_ROLE, $data['roles'], true) && $this->isLastActiveSuperAdmin($user)) {
            return back()->withInput()->with('error', 'This is the only active Super Administrator. Assign the role to someone else first.');
        }

        $before = $user->roles->pluck('name')->all();

        $user->forceFill([
            'name' => $data['name'],
            'email' => Str::lower($data['email']),
            'job_title' => $data['job_title'] ?? null,
        ])->save();
        $user->syncRoles($data['roles']);

        $this->audit()->record('user.updated', $user, 'Updated account for '.$user->email, ['roles' => ['from' => $before, 'to' => $data['roles']]]);

        return redirect()->route('admin.users.index')->with('status', 'User updated.');
    }

    public function toggleActive(Request $request, User $user): RedirectResponse
    {
        $this->guardSuperAdmin($request->user(), $user);

        if ($user->is($request->user())) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }

        if ($user->is_active && $user->isSuperAdmin() && $this->isLastActiveSuperAdmin($user)) {
            return back()->with('error', 'The last active Super Administrator cannot be deactivated.');
        }

        $user->forceFill(['is_active' => ! $user->is_active])->save();

        if (! $user->is_active) {
            DB::table('sessions')->where('user_id', $user->id)->delete();
        }

        $this->audit()->record($user->is_active ? 'user.activated' : 'user.deactivated', $user, ($user->is_active ? 'Activated ' : 'Deactivated ').$user->email);

        return back()->with('status', $user->is_active ? 'Account activated.' : 'Account deactivated and signed out everywhere.');
    }

    public function sendReset(Request $request, User $user): RedirectResponse
    {
        $this->guardSuperAdmin($request->user(), $user);

        $sent = $this->sendInvitation($user);
        $this->audit()->record('user.password_link_sent', $user, 'Sent a password link to '.$user->email);

        return back()->with($sent ? 'status' : 'error', $sent ? 'A password link has been emailed to '.$user->email.'.' : 'The email could not be sent. Check the mail configuration.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->guardSuperAdmin($request->user(), $user);

        if ($user->is($request->user())) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        if ($user->isSuperAdmin() && $this->isLastActiveSuperAdmin($user)) {
            return back()->with('error', 'The last active Super Administrator cannot be deleted.');
        }

        $email = $user->email;
        $user->delete();

        $this->audit()->record('user.deleted', null, 'Deleted account '.$email);

        return redirect()->route('admin.users.index')->with('status', 'User deleted. Content they authored is kept.');
    }

    /**
     * @return Collection<string, string>
     */
    protected function assignableRoles(User $actor): Collection
    {
        return Role::query()
            ->when(! $actor->isSuperAdmin(), fn ($query) => $query->where('name', '!=', Permissions::SUPER_ADMIN_ROLE))
            ->orderBy('name')
            ->pluck('name', 'name');
    }

    protected function guardSuperAdmin(User $actor, User $target): void
    {
        abort_if($target->isSuperAdmin() && ! $actor->isSuperAdmin(), 403, 'Only a Super Administrator can manage this account.');
    }

    protected function isLastActiveSuperAdmin(User $user): bool
    {
        return User::role(Permissions::SUPER_ADMIN_ROLE)->active()->whereKeyNot($user->id)->doesntExist();
    }

    protected function sendInvitation(User $user): bool
    {
        try {
            $token = Password::broker()->createToken($user);
            $user->notify(new AccountInvitation($token));

            return true;
        } catch (Throwable $exception) {
            Log::error('Unable to send account invitation.', ['user' => $user->id, 'error' => $exception->getMessage()]);

            return false;
        }
    }
}
