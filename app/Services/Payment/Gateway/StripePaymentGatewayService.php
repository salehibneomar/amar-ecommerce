<?php

namespace App\Services\Payment\Gateway;

use App\Enums\Order\PaymentStatusEnum;
use App\Models\Payment;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class StripePaymentGatewayService extends PaymentGatewayService
{
    private const ZERO_DECIMAL_CURRENCIES = ['BIF', 'CLP', 'DJF', 'GNF', 'JPY', 'KMF', 'KRW', 'MGA', 'PYG', 'RWF', 'UGX', 'VND', 'VUV', 'XAF', 'XOF', 'XPF'];

    private const THREE_DECIMAL_CURRENCIES = ['BHD', 'JOD', 'KWD', 'OMR', 'TND'];

    public function label(): string
    {
        return __('Stripe');
    }

    public function description(): string
    {
        return __('Pay securely online with a debit or credit card.');
    }

    public function icon(): string
    {
        return 'credit_card';
    }

    public function vendor(): ?string
    {
        return 'Stripe';
    }

    public function isOnline(): bool
    {
        return true;
    }

    public function isConfigured(): bool
    {
        return filled(paymentGatewaySetting('stripe', 'key')) && filled(paymentGatewaySetting('stripe', 'secret'));
    }

    public function pageView(): ?string
    {
        return 'frontend.payments.stripe';
    }

    public function status(Payment $payment): ?PaymentStatusEnum
    {
        if (blank($payment->transaction_id)) {
            return null;
        }

        return $this->statusFromIntent($this->findIntent((string) $payment->transaction_id));
    }

    public function cancel(Payment $payment): void
    {
        if (filled($payment->transaction_id)) {
            $this->request()->post("payment_intents/{$payment->transaction_id}/cancel")->throw();
        }
    }

    public function webhookReference(Request $request): ?string
    {
        $payload = $request->getContent();
        $this->verifySignature($payload, (string) $request->header('Stripe-Signature'));

        /** @var array{type?: string, data?: array{object?: array{metadata?: array{reference?: string}}}} $event */
        $event = json_decode($payload, true) ?: [];

        if (! str_starts_with($event['type'] ?? '', 'payment_intent.')) {
            return null;
        }

        return $event['data']['object']['metadata']['reference'] ?? null;
    }

    /**
     * @return array{publishableKey: string, clientSecret: string, returnUrl: string, email: string, isTestMode: bool}
     */
    public function pageData(Payment $payment): array
    {
        $intent = filled($payment->transaction_id)
            ? $this->findIntent((string) $payment->transaction_id)
            : $this->createIntent($payment);

        $publishableKey = (string) paymentGatewaySetting('stripe', 'key');

        return [
            'publishableKey' => $publishableKey,
            'clientSecret' => (string) $intent['client_secret'],
            'returnUrl' => route('payments.return', $payment->reference),
            'email' => $payment->order->customer_email,
            'isTestMode' => str_starts_with($publishableKey, 'pk_test_'),
        ];
    }

    /**
     * @return array{publishableKey: string, amount: int, currency: string, placeUrl: string, email: string, isTestMode: bool}
     */
    public function checkoutData(int $amount, string $currency, string $email): array
    {
        $publishableKey = (string) paymentGatewaySetting('stripe', 'key');

        return [
            'publishableKey' => $publishableKey,
            'amount' => $this->minorAmount($amount, $currency),
            'currency' => strtolower($currency),
            'placeUrl' => route('payments.place'),
            'email' => $email,
            'isTestMode' => str_starts_with($publishableKey, 'pk_test_'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function createIntent(Payment $payment): array
    {
        $order = $payment->order;

        /** @var array<string, mixed> $intent */
        $intent = $this->request()
            ->withHeaders(['Idempotency-Key' => 'payment-'.$payment->reference])
            ->post('payment_intents', [
                'amount' => $this->minorAmount($payment->amount, $payment->currency),
                'currency' => strtolower($payment->currency),
                'automatic_payment_methods' => ['enabled' => 'true'],
                'description' => __('Order :order', ['order' => $order->public_order_id]),
                'receipt_email' => $order->customer_email,
                'metadata' => ['reference' => $payment->reference, 'order' => $order->public_order_id],
            ])
            ->throw()
            ->json();

        $payment->forceFill(['transaction_id' => $intent['id']])->save();

        return $intent;
    }

    /**
     * @return array<string, mixed>
     */
    private function findIntent(string $intentId): array
    {
        return $this->request()->get("payment_intents/{$intentId}")->throw()->json();
    }

    /**
     * @param  array<string, mixed>  $intent
     */
    private function statusFromIntent(array $intent): PaymentStatusEnum
    {
        return match ($intent['status'] ?? null) {
            'succeeded' => PaymentStatusEnum::Paid,
            'canceled' => PaymentStatusEnum::Cancelled,
            'requires_payment_method' => filled($intent['last_payment_error'] ?? null) ? PaymentStatusEnum::Failed : PaymentStatusEnum::Pending,
            default => PaymentStatusEnum::Pending,
        };
    }

    private function minorAmount(int $amount, string $currency): int
    {
        return match (true) {
            in_array(strtoupper($currency), self::ZERO_DECIMAL_CURRENCIES, true) => $amount,
            in_array(strtoupper($currency), self::THREE_DECIMAL_CURRENCIES, true) => $amount * 1000,
            default => $amount * 100,
        };
    }

    private function verifySignature(string $payload, string $header): void
    {
        $parts = collect(explode(',', $header))
            ->map(fn (string $part): array => explode('=', trim($part), 2) + [1 => ''])
            ->mapToGroups(fn (array $part): array => [$part[0] => $part[1]]);
        $timestamp = (int) $parts->get('t')?->first();
        $expected = hash_hmac('sha256', "{$timestamp}.{$payload}", (string) paymentGatewaySetting('stripe', 'webhook_secret'));

        $isValid = filled(paymentGatewaySetting('stripe', 'webhook_secret'))
            && abs(time() - $timestamp) <= (int) paymentConfig('stripe.webhook_tolerance', 300)
            && collect($parts->get('v1'))->contains(fn (string $signature): bool => hash_equals($expected, $signature));

        if (! $isValid) {
            throw new BadRequestHttpException('Invalid Stripe signature.');
        }
    }

    private function request(): PendingRequest
    {
        return Http::baseUrl((string) paymentGatewaySetting('stripe', 'api_url'))
            ->withToken((string) paymentGatewaySetting('stripe', 'secret'))
            ->asForm()
            ->acceptJson()
            ->timeout(15);
    }
}
