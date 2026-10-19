<?php

namespace App\Interfaces\Payment;

use App\Models\Order;

interface PaymentGatewayInterface
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function confirm(Order $order, array $payload = []): bool;
}
