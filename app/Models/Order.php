<?php

namespace App\Models;

use App\Enums\Order\ContactModeEnum;
use App\Enums\Order\OrderStatusEnum;
use App\Enums\Order\PaymentStatusEnum;
use App\Traits\Model\CmsUpdateTrackerTrait;
use Carbon\CarbonImmutable;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property string $public_order_id
 * @property int|null $user_id
 * @property string $customer_name
 * @property string $customer_email
 * @property int $subtotal
 * @property int $discount_amount
 * @property list<array{name: string, percentage: int, categories: list<string>, amount: int}>|null $applied_offers
 * @property int $extra_discount_percentage
 * @property int $extra_discount_amount
 * @property int $total
 * @property string $currency
 * @property OrderStatusEnum $status
 * @property PaymentStatusEnum $payment_status
 * @property ContactModeEnum|null $contact_mode
 * @property CarbonImmutable|null $customer_contact_recorded_at
 * @property string $phone
 * @property string $shipping_address
 * @property string $billing_address
 * @property string|null $notes
 * @property string|null $internal_note
 * @property CarbonImmutable|null $completed_at
 * @property int|null $completed_by
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property-read bool $has_invoice
 * @property-read int $total_before_extra_discount
 * @property-read string $customer_contact_label
 * @property-read Payment|null $payment
 */
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use CmsUpdateTrackerTrait, HasFactory;

    protected $fillable = [
        'internal_note',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'subtotal' => 'integer',
            'discount_amount' => 'integer',
            'applied_offers' => 'array',
            'extra_discount_percentage' => 'integer',
            'extra_discount_amount' => 'integer',
            'total' => 'integer',
            'status' => OrderStatusEnum::class,
            'payment_status' => PaymentStatusEnum::class,
            'contact_mode' => ContactModeEnum::class,
            'customer_contact_recorded_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return Attribute<bool, never>
     */
    protected function hasInvoice(): Attribute
    {
        return Attribute::get(fn (): bool => $this->payment_status->isPaid());
    }

    /**
     * @return Attribute<non-negative-int, never>
     */
    protected function totalBeforeExtraDiscount(): Attribute
    {
        return Attribute::get(fn (): int => max(0, $this->subtotal - $this->discount_amount));
    }

    /**
     * @return Attribute<string, never>
     */
    protected function customerContactLabel(): Attribute
    {
        return Attribute::get(fn (): string => $this->contact_mode
            ? $this->contact_mode->label().' · '.formatDateTime($this->customer_contact_recorded_at)
            : __('Not contacted yet'));
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * @return HasOne<Payment, $this>
     */
    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function isCancellable(): bool
    {
        return $this->status->isUnresolved() && ! $this->payment_status->isPaid();
    }

    public function isCancellableByCustomer(): bool
    {
        return $this->status === OrderStatusEnum::Pending && $this->isCancellable();
    }

    public function isExtraDiscountEditable(): bool
    {
        return $this->status->isUnresolved() && ! $this->payment_status->isPaid() && ! ($this->payment?->gateway->isOnline() ?? false);
    }
}
