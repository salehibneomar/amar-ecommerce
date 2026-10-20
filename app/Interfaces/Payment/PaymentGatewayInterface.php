<?php

namespace App\Interfaces\Payment;

use App\Enums\Order\PaymentStatusEnum;
use App\Models\Payment;
use Illuminate\Http\Request;

interface PaymentGatewayInterface
{
    public function initiate(Payment $payment): ?string;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function confirm(Payment $payment, array $payload = []): bool;

    public function status(Payment $payment): ?PaymentStatusEnum;

    public function cancel(Payment $payment): void;

    public function webhookReference(Request $request): ?string;

    /**
     * @return array<string, mixed>
     */
    public function pageData(Payment $payment): array;

    /**
     * @return array<string, mixed>
     */
    public function checkoutData(int $amount, string $currency, string $email): array;
}
