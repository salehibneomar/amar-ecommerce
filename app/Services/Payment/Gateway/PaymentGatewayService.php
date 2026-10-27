<?php

namespace App\Services\Payment\Gateway;

use App\Enums\Order\PaymentStatusEnum;
use App\Interfaces\Payment\PaymentGatewayInterface;
use App\Models\Payment;
use Illuminate\Http\Request;

abstract class PaymentGatewayService implements PaymentGatewayInterface
{
    public function vendor(): ?string
    {
        return null;
    }

    public function isOnline(): bool
    {
        return false;
    }

    public function isConfigured(): bool
    {
        return true;
    }

    public function pageView(): ?string
    {
        return null;
    }

    public function initiate(Payment $payment): ?string
    {
        return null;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function confirm(Payment $payment, array $payload = []): bool
    {
        return $this->status($payment) === PaymentStatusEnum::Paid;
    }

    public function status(Payment $payment): ?PaymentStatusEnum
    {
        return null;
    }

    public function cancel(Payment $payment): void {}

    public function webhookReference(Request $request): ?string
    {
        return null;
    }

    /**
     * @return array<string, mixed>
     */
    public function pageData(Payment $payment): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    public function checkoutData(int $amount, string $currency, string $email): array
    {
        return [];
    }
}
