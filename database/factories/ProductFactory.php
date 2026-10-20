<?php

namespace Database\Factories;

use App\Enums\Catalog\ProductBadgeEnum;
use App\Enums\Catalog\ProductStatusEnum;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @template TModel of Product
 *
 * @extends Factory<TModel>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sku' => strtoupper(fake()->unique()->bothify('???-#####')),
            'name' => fake()->unique()->sentence(3),
            'description' => fake()->paragraph(),
            'price' => fake()->numberBetween(100, 1200),
            'discount_percentage' => null,
            'quantity' => fake()->numberBetween(5, 50),
            'status' => ProductStatusEnum::InStock,
            'badge' => fake()->optional()->randomElement(ProductBadgeEnum::cases()),
            'is_featured' => false,
        ];
    }

    public function featured(): static
    {
        return $this->state(fn (array $attributes): array => ['is_featured' => true]);
    }

    public function stock(int $quantity): static
    {
        return $this->state(fn (array $attributes): array => ['quantity' => $quantity]);
    }

    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes): array => ['quantity' => 0, 'status' => ProductStatusEnum::OutOfStock]);
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes): array => ['status' => ProductStatusEnum::Archived]);
    }
}
