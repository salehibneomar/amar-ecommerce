<?php

namespace Database\Seeders;

use App\Enums\User\StaffCompensationTypeEnum;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->superAdmin()->create([
            'name' => 'Super Admin',
            'email' => 'admin@example.com',
        ]);

        $moderator = User::factory()->moderator()->create([
            'name' => 'Moderator',
            'email' => 'moderator@example.com',
        ]);
        $moderator->staffProfile()->create([
            'compensation_type' => StaffCompensationTypeEnum::Percentage,
            'compensation_value' => 5,
        ]);

        User::factory()->editor()->create([
            'name' => 'Editor',
            'email' => 'editor@example.com',
        ]);

        User::factory()->create([
            'name' => 'Demo Customer',
            'email' => 'customer@example.com',
        ]);

        User::factory(8)->create();

        User::factory(2)->blocked()->create();
    }
}
