<?php

namespace Database\Factories;

use App\Enums\Content\BannerStatusEnum;
use App\Models\Banner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Banner>
 */
class BannerFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'subtitle' => fake()->optional()->sentence(),
            'link' => null,
            'sort_order' => 1,
            'status' => BannerStatusEnum::Active,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['status' => BannerStatusEnum::Inactive]);
    }
}
