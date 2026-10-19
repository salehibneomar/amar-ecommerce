<?php

namespace App\Enums\User;

enum StaffCompensationTypeEnum: string
{
    case Salary = 'salary';
    case Percentage = 'percentage';

    public function label(): string
    {
        return match ($this) {
            self::Salary => __('Fixed Salary'),
            self::Percentage => __('Sales Percentage'),
        };
    }

    public function isPercentage(): bool
    {
        return $this === self::Percentage;
    }
}
