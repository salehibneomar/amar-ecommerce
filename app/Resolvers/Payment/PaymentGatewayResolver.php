<?php

declare(strict_types=1);

namespace App\Resolvers\Payment;

use App\Enums\Payment\PaymentGatewayEnum;
use App\Interfaces\Payment\PaymentGatewayInterface;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;

final class PaymentGatewayResolver
{
    public function __construct(private readonly Container $container) {}

    public function resolve(PaymentGatewayEnum $gateway): PaymentGatewayInterface
    {
        $class = paymentGatewayClass($gateway->value);
        $instance = $class ? $this->container->make($class) : null;

        if (! $instance instanceof PaymentGatewayInterface) {
            throw new InvalidArgumentException("No payment gateway is configured for [{$gateway->value}].");
        }

        return $instance;
    }
}
