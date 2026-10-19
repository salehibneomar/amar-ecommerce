<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        ['email' => $email, 'password' => $password] = config('database.seed_admin');

        if (blank($email) || blank($password)) {
            $this->command->warn('Super admin skipped: set DB_SEED_ADMIN_EMAIL and DB_SEED_ADMIN_PASSWORD.');

            return;
        }

        if (User::query()->where('email', $email)->exists()) {
            return;
        }

        User::factory()->superAdmin()->create([
            'name' => 'Super Admin',
            'email' => $email,
            'password' => $password,
        ]);
    }
}
