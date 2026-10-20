<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Demo data for a fresh database: `php artisan migrate --seed`.
 *
 * Demo sign-ins (password "jayyid2026"):
 *   admin@jayyidautos.ng   Administrator
 *   buyer@jayyidautos.ng   Customer
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (DB::table('users')->exists()) {
            $this->command?->warn('Seed skipped: the users table already has data.');

            return;
        }

        DB::transaction(fn () => $this->call(ShowroomSeeder::class));
    }
}
