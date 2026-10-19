<?php

namespace App\Models;

use App\Enums\User\StaffCompensationTypeEnum;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property int $amount
 * @property int $period_month
 * @property int $period_year
 * @property int|null $total_sales_snapshot
 * @property StaffCompensationTypeEnum $compensation_type_snapshot
 * @property int $compensation_value_snapshot
 * @property string|null $notes
 * @property CarbonImmutable|null $paid_at
 * @property int|null $paid_by
 */
class StaffPayment extends Model
{
    protected $fillable = [
        'user_id',
        'amount',
        'period_month',
        'period_year',
        'total_sales_snapshot',
        'compensation_type_snapshot',
        'compensation_value_snapshot',
        'notes',
        'paid_at',
        'paid_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'period_month' => 'integer',
            'period_year' => 'integer',
            'total_sales_snapshot' => 'integer',
            'compensation_type_snapshot' => StaffCompensationTypeEnum::class,
            'compensation_value_snapshot' => 'integer',
            'paid_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function paidBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function periodLabel(): string
    {
        return CarbonImmutable::createFromDate($this->period_year, $this->period_month, 1)->format('F Y');
    }
}
