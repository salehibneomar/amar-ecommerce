<?php

namespace App\Services\Payment\Gateway;

use App\Models\Payment;

class ManualPaymentGatewayService extends PaymentGatewayService
{
    public function label(): string
    {
        return __('Manual Payment');
    }

    public function description(): string
    {
        return __('Send the payment and share the transaction ID with us.');
    }

    public function icon(): string
    {
        return 'smartphone';
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function confirm(Payment $payment, array $payload = []): bool
    {
        return $payment->method !== null && filled($payment->transaction_id);
    }
}
