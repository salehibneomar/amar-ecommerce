<?php

namespace Database\Factories;

use App\Enums\Catalog\ProductBadgeEnum;
use App\Enums\Catalog\ProductStatusEnum;
use App\Enums\Catalog\StockTypeEnum;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @template TModel of Product
 *
 * @extends Factory<TModel>
 */
abstract class ProductFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->sentence(3),
            'description' => fake()->paragraph(),
            'price' => fake()->numberBetween(100, 1200),
            'discount_percentage' => null,
            'quantity' => null,
            'status' => ProductStatusEnum::InStock,
            'stock_type' => StockTypeEnum::Digital,
            'badge' => fake()->optional()->randomElement(ProductBadgeEnum::cases()),
            'is_free' => false,
            'is_featured' => false,
        ];
    }

    public function featured(): static
    {
        return $this->state(fn (array $attributes): array => ['is_featured' => true]);
    }

    public function free(): static
    {
        return $this->state(fn (array $attributes): array => ['is_free' => true, 'price' => 0]);
    }

    public function physical(int $quantity = 20): static
    {
        return $this->state(fn (array $attributes): array => ['stock_type' => StockTypeEnum::Physical, 'quantity' => $quantity]);
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes): array => ['status' => ProductStatusEnum::Archived]);
    }
}
