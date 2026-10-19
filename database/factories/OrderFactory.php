<?php

namespace Database\Factories;

use App\Enums\Order\OrderStatusEnum;
use App\Enums\Order\PaymentMethodEnum;
use App\Enums\Order\PaymentStatusEnum;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $total = fake()->numberBetween(200, 2000);

        return [
            'public_order_id' => 'NK-'.Str::upper(Str::random(6)),
            'user_id' => User::factory(),
            'customer_name' => fn (array $attributes): string => User::query()->find($attributes['user_id'])->name ?? fake()->name(),
            'customer_email' => fn (array $attributes): string => User::query()->find($attributes['user_id'])->email ?? fake()->safeEmail(),
            'subtotal' => $total,
            'discount_amount' => 0,
            'total' => $total,
            'status' => OrderStatusEnum::Requested,
            'payment_status' => PaymentStatusEnum::Pending,
            'phone' => '01'.fake()->numerify('#########'),
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => OrderStatusEnum::Completed,
            'payment_status' => PaymentStatusEnum::Paid,
            'payment_method' => PaymentMethodEnum::Bkash,
            'transaction_id' => Str::upper(Str::random(10)),
            'completed_at' => now(),
        ]);
    }
}
