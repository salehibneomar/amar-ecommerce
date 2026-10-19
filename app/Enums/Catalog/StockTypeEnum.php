<?php

namespace App\Enums\Catalog;

enum StockTypeEnum: string
{
    case Digital = 'digital';
    case Physical = 'physical';

    public function label(): string
    {
        return match ($this) {
            self::Digital => __('Digital'),
            self::Physical => __('Physical'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Digital => 'pink',
            self::Physical => 'yellow',
        };
    }
}
