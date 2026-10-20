<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Production-safe: creates permissions, roles and starter site content
     * only where missing. Outside APP_ENV=local no user accounts are created:
     * provision the first administrator with `php artisan dxcovr:create-admin`.
     * Locally, LocalAdminSeeder creates an account from .env.
     *
     * Demonstration content is never seeded here; run it explicitly in a
     * local environment with `php artisan db:seed --class=DemoContentSeeder`.
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            SiteContentSeeder::class,
            LocalAdminSeeder::class,
        ]);
    }
}
