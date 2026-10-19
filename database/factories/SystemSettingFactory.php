<?php

namespace Database\Factories;

use App\Models\SystemSetting;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SystemSetting>
 */
class SystemSettingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'maintenance_enabled' => false,
        ];
    }

    public function maintenance(): static
    {
        return $this->state(fn (): array => [
            'maintenance_enabled' => true,
            'maintenance_secret' => Str::random(40),
        ]);
    }
}
