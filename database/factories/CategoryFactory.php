<?php

namespace Database\Factories;

use App\Enums\Catalog\CategoryStatusEnum;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'description' => fake()->optional()->sentence(),
            'depth' => 1,
            'status' => CategoryStatusEnum::Active,
            'sort_order' => 1,
            'is_featured' => false,
        ];
    }

    public function featured(): static
    {
        return $this->state(fn (array $attributes): array => ['is_featured' => true]);
    }

    public function childOf(Category $parent): static
    {
        return $this->state(fn (array $attributes): array => ['parent_id' => $parent->id, 'depth' => $parent->depth + 1]);
    }
}
