<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Non-sensitive reference data only. Safe to run repeatedly.
 * Staff accounts are created with `php artisan fco:create-admin` or the web installer.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            LanguageSeeder::class,
            AccessControlSeeder::class,
            ReferenceDataSeeder::class,
        ]);
    }
}
