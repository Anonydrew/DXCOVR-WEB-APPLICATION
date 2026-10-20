<?php

namespace App\Http\Controllers\Admin;

use App\Support\Permissions;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Roles and their permissions. The Super Administrator role always has every
 * permission (via Gate::before) and cannot be edited or deleted.
 */
class RoleController extends AdminController
{
    public function index(): View
    {
        return view('admin.roles.index', [
            'roles' => Role::query()->withCount(['users', 'permissions'])->orderBy('name')->get(),
            'descriptions' => collect(Permissions::roles())->map(fn ($role) => $role['description']),
        ]);
    }

    public function create(): View
    {
        return view('admin.roles.form', ['role' => new Role, 'selected' => [], 'groups' => Permissions::grouped()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $role = Role::create(['name' => $data['name'], 'guard_name' => 'web']);
        $role->syncPermissions($data['permissions'] ?? []);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->audit()->record('user.role_created', $role, 'Created role '.$role->name, ['permissions' => $data['permissions'] ?? []]);

        return redirect()->route('admin.roles.index')->with('status', 'Role created.');
    }

    public function edit(Role $role): View|RedirectResponse
    {
        if ($role->name === Permissions::SUPER_ADMIN_ROLE) {
            return redirect()->route('admin.roles.index')->with('error', 'The Super Administrator role always has every permission and cannot be edited.');
        }

        return view('admin.roles.form', [
            'role' => $role,
            'selected' => $role->permissions()->pluck('name')->all(),
            'groups' => Permissions::grouped(),
        ]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        abort_if($role->name === Permissions::SUPER_ADMIN_ROLE, 403);

        $data = $this->validated($request, $role);
        $before = $role->permissions()->pluck('name')->all();

        $role->update(['name' => $data['name']]);
        $role->syncPermissions($data['permissions'] ?? []);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->audit()->record('user.role_updated', $role, 'Updated role '.$role->name, [
            'added' => array_values(array_diff($data['permissions'] ?? [], $before)),
            'removed' => array_values(array_diff($before, $data['permissions'] ?? [])),
        ]);

        return redirect()->route('admin.roles.index')->with('status', 'Role updated. Changes apply on each user’s next request.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        abort_if($role->name === Permissions::SUPER_ADMIN_ROLE, 403);

        if ($role->users()->exists()) {
            return back()->with('error', 'Reassign the users with this role before deleting it.');
        }

        $name = $role->name;
        $role->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->audit()->record('user.role_deleted', null, 'Deleted role '.$name);

        return redirect()->route('admin.roles.index')->with('status', 'Role deleted.');
    }

    /**
     * @return array{name: string, permissions?: list<string>}
     */
    protected function validated(Request $request, ?Role $role = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:60', Rule::unique('roles', 'name')->ignore($role?->id), Rule::notIn([Permissions::SUPER_ADMIN_ROLE])],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => [Rule::in(Permissions::all())],
        ]);
    }
}
