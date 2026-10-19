<?php

namespace Database\Factories;

use App\Enums\Content\FaqStatusEnum;
use App\Models\Faq;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Faq>
 */
class FaqFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => rtrim(fake()->sentence(), '.').'?',
            'description' => fake()->paragraph(),
            'status' => FaqStatusEnum::Active,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['status' => FaqStatusEnum::Inactive]);
    }
}
