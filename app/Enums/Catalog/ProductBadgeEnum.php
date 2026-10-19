<?php

namespace App\Enums\Catalog;

enum ProductBadgeEnum: string
{
    case New = 'new';
    case Hot = 'hot';
    case Sale = 'sale';

    public function label(): string
    {
        return match ($this) {
            self::New => __('New'),
            self::Hot => __('Hot'),
            self::Sale => __('Sale'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::New => 'lime',
            self::Hot => 'orange',
            self::Sale => 'green',
        };
    }

    public static function dbComment(): string
    {
        return implode(', ', array_map(fn (self $case): string => $case->value, self::cases()));
    }
}
