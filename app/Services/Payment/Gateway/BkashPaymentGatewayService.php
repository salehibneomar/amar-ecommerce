<?php

namespace App\Services\Payment\Gateway;

use App\Enums\Order\PaymentStatusEnum;
use App\Models\Payment;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class BkashPaymentGatewayService extends PaymentGatewayService
{
    private const TOKEN_CACHE_KEY = 'payment:bkash:token';

    private const SUCCESS_CODE = '0000';

    private const CURRENCY = 'BDT';

    private const FAILED_STATUSES = ['Failed', 'Cancelled', 'Expired', 'Declined'];

    public function label(): string
    {
        return __('bKash');
    }

    public function description(): string
    {
        return __('Pay instantly from your bKash account.');
    }

    public function icon(): string
    {
        return 'phone_iphone';
    }

    public function isOnline(): bool
    {
        return true;
    }

    public function isConfigured(): bool
    {
        return strtoupper(appCurrency()) === self::CURRENCY
            && filled(paymentGatewaySetting('bkash', 'app_key'))
            && filled(paymentGatewaySetting('bkash', 'app_secret'))
            && filled(paymentGatewaySetting('bkash', 'username'))
            && filled(paymentGatewaySetting('bkash', 'password'));
    }

    public function initiate(Payment $payment): ?string
    {
        $created = $this->call('tokenized/checkout/create', [
            'mode' => '0011',
            'payerReference' => $payment->order->public_order_id,
            'callbackURL' => route('payments.return', $payment->reference),
            'amount' => (string) $payment->amount,
            'currency' => strtoupper($payment->currency),
            'intent' => 'sale',
            'merchantInvoiceNumber' => $payment->reference.'-'.now()->getTimestamp(),
        ]);

        if (! $this->isSuccessful($created) || blank($created['bkashURL'] ?? null)) {
            report(new RuntimeException('bKash payment could not be created: '.($created['statusMessage'] ?? 'unknown reason')));

            return null;
        }

        $payment->forceFill(['transaction_id' => $created['paymentID']])->save();

        return (string) $created['bkashURL'];
    }

    public function status(Payment $payment): ?PaymentStatusEnum
    {
        if (blank($payment->transaction_id)) {
            return null;
        }

        $result = $this->call('tokenized/checkout/payment/status', ['paymentID' => $payment->transaction_id]);

        if (($result['transactionStatus'] ?? null) === 'Initiated') {
            $result = $this->call('tokenized/checkout/execute', ['paymentID' => $payment->transaction_id]);

            if (! $this->isSuccessful($result)) {
                return PaymentStatusEnum::Failed;
            }
        }

        return match (true) {
            $this->isPaid($result, $payment) => PaymentStatusEnum::Paid,
            in_array($result['transactionStatus'] ?? null, self::FAILED_STATUSES, true) => PaymentStatusEnum::Failed,
            default => PaymentStatusEnum::Pending,
        };
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function isPaid(array $result, Payment $payment): bool
    {
        return $this->isSuccessful($result)
            && ($result['transactionStatus'] ?? null) === 'Completed'
            && ($result['paymentID'] ?? null) === $payment->transaction_id
            && strtoupper((string) ($result['currency'] ?? '')) === strtoupper($payment->currency)
            && round((float) ($result['amount'] ?? 0), 2) === (float) $payment->amount;
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function isSuccessful(array $result): bool
    {
        return ($result['statusCode'] ?? null) === self::SUCCESS_CODE;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function call(string $endpoint, array $payload): array
    {
        return $this->request()
            ->withHeaders(['Authorization' => $this->token(), 'X-APP-Key' => (string) paymentGatewaySetting('bkash', 'app_key')])
            ->post($endpoint, $payload)
            ->throw()
            ->json() ?? [];
    }

    private function token(): string
    {
        if ($token = Cache::get(self::TOKEN_CACHE_KEY)) {
            return (string) $token;
        }

        /** @var array{id_token?: string, expires_in?: int, statusMessage?: string} $grant */
        $grant = $this->request()
            ->withHeaders(['username' => (string) paymentGatewaySetting('bkash', 'username'), 'password' => (string) paymentGatewaySetting('bkash', 'password')])
            ->post('tokenized/checkout/token/grant', [
                'app_key' => (string) paymentGatewaySetting('bkash', 'app_key'),
                'app_secret' => (string) paymentGatewaySetting('bkash', 'app_secret'),
            ])
            ->throw()
            ->json();

        if (blank($grant['id_token'] ?? null)) {
            throw new RuntimeException('bKash token could not be granted: '.($grant['statusMessage'] ?? 'unknown reason'));
        }

        Cache::put(self::TOKEN_CACHE_KEY, $grant['id_token'], max(60, (int) ($grant['expires_in'] ?? 3600) - 300));

        return $grant['id_token'];
    }

    private function request(): PendingRequest
    {
        return Http::baseUrl((string) paymentGatewaySetting('bkash', 'api_url'))
            ->asJson()
            ->acceptJson()
            ->timeout(30);
    }
}
