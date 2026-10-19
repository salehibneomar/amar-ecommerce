<?php

declare(strict_types=1);

namespace App\Resolvers\Payment;

use App\Enums\Order\PaymentMethodEnum;
use App\Interfaces\Payment\PaymentGatewayInterface;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;

final class PaymentGatewayResolver
{
    public function __construct(private readonly Container $container) {}

    public function resolve(PaymentMethodEnum $method): PaymentGatewayInterface
    {
        $gateway = paymentGatewayClass($method->value);
        $instance = $gateway ? $this->container->make($gateway) : null;

        if (! $instance instanceof PaymentGatewayInterface) {
            throw new InvalidArgumentException("No payment gateway is configured for [{$method->value}].");
        }

        return $instance;
    }
}
