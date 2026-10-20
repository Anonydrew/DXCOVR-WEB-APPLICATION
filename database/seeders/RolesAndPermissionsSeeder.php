<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

/**
 * Creates permissions and default roles. Idempotent; never removes custom roles.
 */
class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        Artisan::call('dxcovr:sync-permissions');
    }
}
