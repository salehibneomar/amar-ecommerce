<?php

namespace App\Services\Payment;

use App\Enums\Order\PaymentMethodEnum;
use App\Enums\Order\PaymentStatusEnum;
use App\Enums\Payment\PaymentGatewayEnum;
use App\Models\Order;
use App\Models\Payment;
use App\Resolvers\Payment\PaymentGatewayResolver;
use App\Services\Backoffice\Payment\PaymentGatewaySettingService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class PaymentService
{
    public function __construct(
        private readonly PaymentGatewayResolver $gatewayResolver,
        private readonly PaymentGatewaySettingService $gatewaySettingService,
    ) {}

    /**
     * @return list<PaymentGatewayEnum>
     */
    public function gateways(): array
    {
        return array_values(array_filter($this->gatewaySettingService->enabledGateways(), fn (PaymentGatewayEnum $gateway): bool => $gateway->isConfigured()));
    }

    public function isManual(Order $order): bool
    {
        return $order->payment?->gateway->isManual() ?? false;
    }

    public function isPayable(Payment $payment): bool
    {
        return $payment->gateway->isOnline()
            && in_array($payment->status, [PaymentStatusEnum::Pending, PaymentStatusEnum::Failed], true)
            && $payment->order->status->isUnresolved();
    }

    public function payUrl(Order $order): ?string
    {
        $payment = $order->payment?->setRelation('order', $order);

        return $payment && $this->isPayable($payment) ? URL::signedRoute('payments.show', $payment->reference) : null;
    }

    public function hostedUrl(Payment $payment): ?string
    {
        try {
            return $this->gatewayResolver->resolve($payment->gateway)->initiate($payment);
        } catch (ConnectionException|RequestException|RuntimeException $exception) {
            report($exception);

            return null;
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function page(Payment $payment): array
    {
        try {
            return $this->gatewayResolver->resolve($payment->gateway)->pageData($payment);
        } catch (ConnectionException|RequestException $exception) {
            report($exception);

            return ['unavailable' => true];
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function checkoutPage(PaymentGatewayEnum $gateway, int $amount, string $email): array
    {
        return $this->gatewayResolver->resolve($gateway)->checkoutData($amount, appCurrency(), $email);
    }

    public function sync(Payment $payment): PaymentStatusEnum
    {
        $status = $this->gatewayResolver->resolve($payment->gateway)->status($payment);

        if ($status === null) {
            return $payment->status;
        }

        return DB::transaction(function () use ($payment, $status): PaymentStatusEnum {
            $payment = Payment::query()->with('order')->lockForUpdate()->findOrFail($payment->id);

            if ($payment->status === $status || $payment->status === PaymentStatusEnum::Refunded) {
                return $payment->status;
            }

            $payment->forceFill([
                'status' => $status,
                'paid_at' => $status === PaymentStatusEnum::Paid ? ($payment->paid_at ?? now()) : null,
            ])->save();

            if ((int) $payment->order->payments()->max('id') === $payment->id) {
                $payment->order->forceFill(['payment_status' => $status])->save();
            }

            return $status;
        });
    }

    public function handleWebhook(PaymentGatewayEnum $gateway, Request $request): void
    {
        $reference = $this->gatewayResolver->resolve($gateway)->webhookReference($request);
        $payment = $reference ? Payment::query()->where('gateway', $gateway)->where('reference', $reference)->first() : null;

        if ($payment) {
            $this->sync($payment);
        }
    }

    /**
     * @return list<Unique|string>
     */
    public function transactionIdRules(?PaymentMethodEnum $method = null, ?int $ignoreOrderId = null): array
    {
        return [
            'required',
            'string',
            'alpha_num',
            'min:6',
            'max:64',
            Rule::unique('payments', 'transaction_id')
                ->where('gateway', PaymentGatewayEnum::Manual->value)
                ->where('method', $method?->value)
                ->ignore($ignoreOrderId, 'order_id'),
        ];
    }

    public function open(Order $order, PaymentGatewayEnum $gateway): Payment
    {
        return $order->payments()->forceCreate([
            'gateway' => $gateway,
            'status' => PaymentStatusEnum::Pending,
            'amount' => $order->total,
            'currency' => $order->currency,
            'reference' => (string) Str::ulid(),
        ])->setRelation('order', $order);
    }

    public function record(Order $order, PaymentStatusEnum $status, ?PaymentMethodEnum $method = null, ?string $transactionId = null): void
    {
        if ($status === PaymentStatusEnum::Refunded && ! in_array($order->payment_status, [PaymentStatusEnum::Paid, PaymentStatusEnum::Refunded], true)) {
            throw ValidationException::withMessages(['orderPaymentStatus' => __('Only a paid order can be refunded.')]);
        }

        $order->payment_status = $status;
        $payment = $order->payments()->latest('id')->lockForUpdate()->first();

        if ($payment === null) {
            return;
        }

        $gateway = $this->gatewayResolver->resolve($payment->gateway);

        $payment->forceFill([
            ...($payment->gateway->isManual() ? [
                'method' => $method,
                'transaction_id' => filled($transactionId) ? strtoupper(trim((string) $transactionId)) : null,
            ] : []),
            'status' => $status,
            'amount' => $order->total,
            'currency' => $order->currency,
            'paid_at' => match ($status) {
                PaymentStatusEnum::Paid => $payment->paid_at ?? now(),
                PaymentStatusEnum::Refunded => $payment->paid_at,
                default => null,
            },
        ]);

        if ($status === PaymentStatusEnum::Paid && ! $gateway->confirm($payment)) {
            throw ValidationException::withMessages(['trxId' => __('The payment could not be confirmed.')]);
        }

        if ($status === PaymentStatusEnum::Cancelled && $payment->isDirty('status')) {
            try {
                $gateway->cancel($payment);
            } catch (ConnectionException|RequestException $exception) {
                report($exception);

                throw ValidationException::withMessages(['order' => __('The online payment could not be cancelled. Please try again.')]);
            }
        }

        $payment->save();
    }
}
