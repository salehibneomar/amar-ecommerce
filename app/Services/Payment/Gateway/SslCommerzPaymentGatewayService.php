<?php

namespace App\Services\Payment\Gateway;

use App\Enums\Order\PaymentStatusEnum;
use App\Models\Payment;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class SslCommerzPaymentGatewayService extends PaymentGatewayService
{
    private const PAID_STATUSES = ['VALID', 'VALIDATED'];

    private const FAILED_STATUSES = ['FAILED', 'CANCELLED', 'EXPIRED'];

    public function label(): string
    {
        return __('SSLCommerz');
    }

    public function description(): string
    {
        return __('Pay with a card, mobile banking or internet banking.');
    }

    public function icon(): string
    {
        return 'account_balance_wallet';
    }

    public function vendor(): ?string
    {
        return 'SSLCommerz';
    }

    public function isOnline(): bool
    {
        return true;
    }

    public function isConfigured(): bool
    {
        return filled(paymentGatewaySetting('sslcommerz', 'store_id')) && filled(paymentGatewaySetting('sslcommerz', 'store_password'));
    }

    public function initiate(Payment $payment): ?string
    {
        $order = $payment->order;
        $callbackUrl = route('payments.callback', $payment->reference);

        /** @var array{status?: string, GatewayPageURL?: string, failedreason?: string} $session */
        $session = $this->request()->post('gwprocess/v4/api.php', [
            ...$this->credentials(),
            'total_amount' => $payment->amount,
            'currency' => strtoupper($payment->currency),
            'tran_id' => $payment->reference,
            'success_url' => $callbackUrl,
            'fail_url' => $callbackUrl,
            'cancel_url' => $callbackUrl,
            'ipn_url' => route('payments.webhook', 'sslcommerz'),
            'cus_name' => Str::limit($order->customer_name, 50, ''),
            'cus_email' => Str::limit($order->customer_email, 50, ''),
            'cus_phone' => Str::limit((string) $order->phone, 20, ''),
            'cus_add1' => Str::limit((string) $order->shipping_address, 50, ''),
            'cus_city' => Str::limit((string) $order->shipping_address, 50, ''),
            'cus_country' => (string) paymentGatewaySetting('sslcommerz', 'country'),
            'shipping_method' => 'NO',
            'product_name' => __('Order :order', ['order' => $order->public_order_id]),
            'product_category' => 'general',
            'product_profile' => 'general',
        ])->throw()->json();

        if (($session['status'] ?? null) !== 'SUCCESS' || blank($session['GatewayPageURL'] ?? null)) {
            report(new RuntimeException('SSLCommerz session failed: '.($session['failedreason'] ?? 'unknown reason')));

            return null;
        }

        return $session['GatewayPageURL'];
    }

    public function status(Payment $payment): ?PaymentStatusEnum
    {
        /** @var array{element?: list<array<string, mixed>>} $result */
        $result = $this->request()->get('validator/api/merchantTransIDvalidationAPI.php', [
            ...$this->credentials(),
            'tran_id' => $payment->reference,
            'format' => 'json',
        ])->throw()->json();
        $transactions = collect($result['element'] ?? []);

        if ($transactions->isEmpty()) {
            return null;
        }

        return match (true) {
            $transactions->contains(fn (array $transaction): bool => $this->isPaid($transaction, $payment)) => PaymentStatusEnum::Paid,
            in_array($transactions->last()['status'] ?? null, self::FAILED_STATUSES, true) => PaymentStatusEnum::Failed,
            default => PaymentStatusEnum::Pending,
        };
    }

    public function webhookReference(Request $request): ?string
    {
        return $request->string('tran_id')->value() ?: null;
    }

    /**
     * @param  array<string, mixed>  $transaction
     */
    private function isPaid(array $transaction, Payment $payment): bool
    {
        $currency = (string) ($transaction['currency_type'] ?? $transaction['currency'] ?? '');
        $amount = (float) ($transaction['currency_amount'] ?? $transaction['amount'] ?? 0);

        return in_array($transaction['status'] ?? null, self::PAID_STATUSES, true)
            && ($transaction['tran_id'] ?? null) === $payment->reference
            && strtoupper($currency) === strtoupper($payment->currency)
            && round($amount, 2) === (float) $payment->amount;
    }

    /**
     * @return array{store_id: string, store_passwd: string}
     */
    private function credentials(): array
    {
        return [
            'store_id' => (string) paymentGatewaySetting('sslcommerz', 'store_id'),
            'store_passwd' => (string) paymentGatewaySetting('sslcommerz', 'store_password'),
        ];
    }

    private function request(): PendingRequest
    {
        return Http::baseUrl((string) paymentGatewaySetting('sslcommerz', 'api_url'))
            ->asForm()
            ->acceptJson()
            ->timeout(15);
    }
}
