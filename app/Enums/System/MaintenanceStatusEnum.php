<?php

namespace App\Enums\System;

enum MaintenanceStatusEnum: string
{
    case Off = 'off';
    case Scheduled = 'scheduled';
    case Active = 'active';
    case Ended = 'ended';

    public function label(): string
    {
        return match ($this) {
            self::Off => __('Off'),
            self::Scheduled => __('Scheduled'),
            self::Active => __('Active'),
            self::Ended => __('Ended'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Off => 'zinc',
            self::Scheduled => 'amber',
            self::Active => 'red',
            self::Ended => 'blue',
        };
    }
}
