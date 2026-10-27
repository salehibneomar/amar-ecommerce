<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\SeoMeta;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SeoMeta>
 */
class SeoMetaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'seoable_type' => Product::class,
            'seoable_id' => Product::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->text(150),
            'noindex' => false,
        ];
    }

    public function noindex(): static
    {
        return $this->state(fn (): array => ['noindex' => true]);
    }
}
