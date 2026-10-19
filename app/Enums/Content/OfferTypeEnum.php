<?php

namespace App\Enums\Content;

enum OfferTypeEnum: string
{
    case Global = 'global';
    case Specific = 'specific';

    public function label(): string
    {
        return match ($this) {
            self::Global => __('Global'),
            self::Specific => __('Specific'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Global => 'violet',
            self::Specific => 'sky',
        };
    }
}
