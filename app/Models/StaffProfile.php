<?php

namespace App\Models;

use App\Enums\User\StaffCompensationTypeEnum;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property StaffCompensationTypeEnum $compensation_type
 * @property int $compensation_value
 * @property CarbonImmutable|null $joined_at
 * @property CarbonImmutable|null $left_at
 */
class StaffProfile extends Model
{
    protected $fillable = [
        'user_id',
        'compensation_type',
        'compensation_value',
        'joined_at',
        'left_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'compensation_type' => StaffCompensationTypeEnum::class,
            'compensation_value' => 'integer',
            'joined_at' => 'date',
            'left_at' => 'date',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function compensationLabel(): string
    {
        return $this->compensation_type->isPercentage()
            ? __(':value% of monthly sales', ['value' => $this->compensation_value])
            : __(':value per month', ['value' => formatMoney($this->compensation_value)]);
    }
}
