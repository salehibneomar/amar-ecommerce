<?php

namespace App\Services\Payment;

use App\Interfaces\Payment\PaymentGatewayInterface;
use App\Models\Order;

class ManualPaymentGatewayService implements PaymentGatewayInterface
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function confirm(Order $order, array $payload = []): bool
    {
        return filled($order->transaction_id);
    }
}
