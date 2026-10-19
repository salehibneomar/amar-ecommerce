<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $order_id
 * @property int|null $product_id
 * @property string $product_name_snapshot
 * @property int $quantity
 * @property int $unit_price
 * @property int $discount_percentage
 * @property string|null $offer_name_snapshot
 * @property int $offer_discount_percentage
 * @property int $line_total
 * @property-read int $original_total
 */
class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'product_name_snapshot',
        'quantity',
        'unit_price',
        'discount_percentage',
        'offer_name_snapshot',
        'offer_discount_percentage',
        'line_total',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'integer',
            'discount_percentage' => 'integer',
            'offer_discount_percentage' => 'integer',
            'line_total' => 'integer',
        ];
    }

    /**
     * @return Attribute<int, never>
     */
    protected function originalTotal(): Attribute
    {
        return Attribute::get(fn (): int => $this->unit_price * $this->quantity);
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
