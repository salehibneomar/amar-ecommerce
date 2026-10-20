<?php

namespace App\Traits\Payment;

use App\Enums\Order\PaymentStatusEnum;
use App\Models\Payment;
use Illuminate\Http\Request;

trait OfflinePaymentGatewayTrait
{
    public function initiate(Payment $payment): ?string
    {
        return null;
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
