<?php

namespace App\Enums\Payment;

use App\Interfaces\Payment\PaymentGatewayInterface;
use App\Resolvers\Payment\PaymentGatewayResolver;

enum PaymentGatewayEnum: string
{
    case Cod = 'cod';
    case Manual = 'manual';
    case Stripe = 'stripe';
    case SslCommerz = 'sslcommerz';
    case Bkash = 'bkash';

    /**
     * @return list<self>
     */
    public static function available(): array
    {
        return array_values(array_filter(self::cases(), fn (self $gateway): bool => paymentGatewayClass($gateway->value) !== null));
    }

    public function driver(): PaymentGatewayInterface
    {
        return app(PaymentGatewayResolver::class)->resolve($this);
    }

    public function label(): string
    {
        return $this->driver()->label();
    }

    public function vendor(): ?string
    {
        return $this->driver()->vendor();
    }

    public function description(): string
    {
        return $this->driver()->description();
    }

    public function icon(): string
    {
        return $this->driver()->icon();
    }

    /**
     * @return view-string|null
     */
    public function pageView(): ?string
    {
        return $this->driver()->pageView();
    }

    public function isManual(): bool
    {
        return $this === self::Manual;
    }

    public function isOnline(): bool
    {
        return $this->driver()->isOnline();
    }

    public function isConfigured(): bool
    {
        return $this->driver()->isConfigured();
    }
}
