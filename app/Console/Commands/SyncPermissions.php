<?php

namespace App\Console\Commands;

use App\Support\Permissions;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

#[Signature('dxcovr:sync-permissions {--reset-roles : Reset the default roles to their default permission sets}')]
#[Description('Create missing permissions and default roles. Safe to run repeatedly in production.')]
class SyncPermissions extends Command
{
    public function handle(PermissionRegistrar $registrar): int
    {
        $registrar->forgetCachedPermissions();

        foreach (Permissions::all() as $name) {
            Permission::findOrCreate($name, 'web');
        }

        foreach (Permissions::roles() as $roleName => $definition) {
            $role = Role::query()->where('name', $roleName)->where('guard_name', 'web')->first();
            $isNew = $role === null;
            $role ??= Role::create(['name' => $roleName, 'guard_name' => 'web']);

            if ($isNew || $this->option('reset-roles')) {
                $role->syncPermissions($definition['permissions']);
            }
        }

        $registrar->forgetCachedPermissions();

        $this->components->info('Permissions and default roles are in sync.');

        return self::SUCCESS;
    }
}
