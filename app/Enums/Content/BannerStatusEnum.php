<?php

namespace App\Enums\Content;

enum BannerStatusEnum: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Inactive = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::Draft => __('Draft'),
            self::Active => __('Live'),
            self::Inactive => __('Inactive'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'yellow',
            self::Active => 'green',
            self::Inactive => 'red',
        };
    }
}
