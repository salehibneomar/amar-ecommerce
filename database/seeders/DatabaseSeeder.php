<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RoleSeeder::class);

        if (! config('database.seeding_enabled')) {
            $this->call(SuperAdminSeeder::class);
            $this->command->warn('Demo data seeding is disabled (DB_SEEDING_ENABLED=false). Only roles and the super admin were seeded.');

            return;
        }

        $this->call([
            UserSeeder::class,
            CategorySeeder::class,
            BookSeeder::class,
            BannerSeeder::class,
            OfferSeeder::class,
            FaqSeeder::class,
            OrderSeeder::class,
        ]);
    }
}
