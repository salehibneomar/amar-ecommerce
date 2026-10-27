<?php

namespace Database\Factories;

use App\Enums\Payment\PaymentGatewayEnum;
use App\Models\PaymentGateway;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentGateway>
 */
class PaymentGatewayFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'gateway' => PaymentGatewayEnum::Cod,
            'is_enabled' => true,
            'sort_order' => 0,
            'settings' => null,
        ];
    }

    public function enabled(): static
    {
        return $this->state(fn (): array => ['is_enabled' => true]);
    }

    public function disabled(): static
    {
        return $this->state(fn (): array => ['is_enabled' => false]);
    }

    public function manual(?string $bkashNumber = '01712345678', ?string $nagadNumber = null): static
    {
        return $this->state(fn (): array => [
            'gateway' => PaymentGatewayEnum::Manual,
            'settings' => ['bkash_number' => $bkashNumber, 'nagad_number' => $nagadNumber],
        ]);
    }
}
