<?php

namespace App\Models;

use App\Enums\Order\PaymentMethodEnum;
use App\Enums\Order\PaymentStatusEnum;
use App\Enums\Payment\PaymentGatewayEnum;
use Carbon\CarbonImmutable;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $order_id
 * @property PaymentGatewayEnum $gateway
 * @property PaymentMethodEnum|null $method
 * @property PaymentStatusEnum $status
 * @property int $amount
 * @property string $currency
 * @property string $reference
 * @property string|null $transaction_id
 * @property array<string, mixed>|null $payload
 * @property CarbonImmutable|null $paid_at
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property-read Order $order
 */
class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'gateway' => PaymentGatewayEnum::class,
            'method' => PaymentMethodEnum::class,
            'status' => PaymentStatusEnum::class,
            'amount' => 'integer',
            'payload' => 'array',
            'paid_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function methodLabel(): string
    {
        return $this->method?->label() ?? implode(' / ', array_unique(array_filter([$this->gateway->vendor(), $this->gateway->label()])));
    }
}
