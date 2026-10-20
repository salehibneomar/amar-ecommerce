<?php

namespace App\Enums\Payment;

enum PaymentGatewayEnum: string
{
    case Cod = 'cod';
    case Manual = 'manual';
    case Stripe = 'stripe';

    /**
     * @return list<self>
     */
    public static function enabled(): array
    {
        return array_values(array_filter(array_map(self::tryFrom(...), paymentGateways())));
    }

    public function label(): string
    {
        return match ($this) {
            self::Cod => __('Cash on Delivery'),
            self::Manual => __('bKash or Nagad'),
            self::Stripe => __('Card'),
        };
    }

    public function vendor(): ?string
    {
        return match ($this) {
            self::Cod, self::Manual => null,
            self::Stripe => 'Stripe',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Cod => __('Pay in cash when your order arrives.'),
            self::Manual => __('Send the payment and share the transaction ID with us.'),
            self::Stripe => __('Pay securely online with a debit or credit card.'),
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Cod => 'payments',
            self::Manual => 'smartphone',
            self::Stripe => 'credit_card',
        };
    }

    /**
     * @return view-string|null
     */
    public function pageView(): ?string
    {
        return match ($this) {
            self::Cod, self::Manual => null,
            self::Stripe => 'frontend.payments.stripe',
        };
    }

    public function isManual(): bool
    {
        return $this === self::Manual;
    }

    public function isOnline(): bool
    {
        return match ($this) {
            self::Cod, self::Manual => false,
            self::Stripe => true,
        };
    }
}
