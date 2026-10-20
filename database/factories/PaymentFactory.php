<?php

namespace Database\Factories;

use App\Enums\Order\PaymentMethodEnum;
use App\Enums\Order\PaymentStatusEnum;
use App\Enums\Payment\PaymentGatewayEnum;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'gateway' => PaymentGatewayEnum::Manual,
            'status' => PaymentStatusEnum::Pending,
            'amount' => fn (array $attributes): int => Order::query()->find($attributes['order_id'])->total ?? 0,
            'currency' => appCurrency(),
            'reference' => (string) Str::ulid(),
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PaymentStatusEnum::Paid,
            'method' => PaymentMethodEnum::Bkash,
            'transaction_id' => Str::upper(Str::random(10)),
            'paid_at' => now(),
        ]);
    }
}
