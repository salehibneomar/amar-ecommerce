<?php

namespace App\Services\Payment\Gateway;

use App\Models\Payment;

class CodPaymentGatewayService extends PaymentGatewayService
{
    public function label(): string
    {
        return __('Cash on Delivery');
    }

    public function description(): string
    {
        return __('Pay in cash when your order arrives.');
    }

    public function icon(): string
    {
        return 'payments';
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function confirm(Payment $payment, array $payload = []): bool
    {
        return true;
    }
}
