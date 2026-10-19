<?php

namespace Database\Factories;

use App\Enums\Content\OfferTypeEnum;
use App\Models\Offer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Offer>
 */
class OfferFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'headline' => fake()->sentence(4),
            'type' => OfferTypeEnum::Global,
            'description' => fake()->optional()->sentence(),
            'discount_percentage' => fake()->numberBetween(5, 30),
            'is_active' => true,
            'starts_at' => null,
            'ends_at' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => ['is_active' => false]);
    }
}
