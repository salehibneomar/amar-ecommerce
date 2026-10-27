<?php

namespace App\Enums\System;

enum ThemeColorEnum: string
{
    case Default = 'default';
    case Teal = 'teal';
    case Gold = 'gold';
    case Pink = 'pink';
    case Violet = 'violet';

    public function label(): string
    {
        return match ($this) {
            self::Default => __('Default'),
            self::Teal => __('Teal'),
            self::Gold => __('Gold'),
            self::Pink => __('Pink'),
            self::Violet => __('Violet'),
        };
    }
}
