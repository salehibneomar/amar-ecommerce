<?php

namespace App\Services\Payment;

use App\Interfaces\Payment\PaymentGatewayInterface;
use App\Models\Payment;
use App\Traits\Payment\OfflinePaymentGatewayTrait;

class CodPaymentGatewayService implements PaymentGatewayInterface
{
    use OfflinePaymentGatewayTrait;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function confirm(Payment $payment, array $payload = []): bool
    {
        return true;
    }
}
