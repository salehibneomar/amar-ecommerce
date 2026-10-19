<?php

namespace App\Enums\User;

enum UserStatusEnum: string
{
    case Active = 'active';
    case Blocked = 'blocked';

    public function label(): string
    {
        return match ($this) {
            self::Active => __('Active'),
            self::Blocked => __('Blocked'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Active => 'green',
            self::Blocked => 'red',
        };
    }

    public function isActive(): bool
    {
        return $this === self::Active;
    }

    public function toggled(): self
    {
        return match ($this) {
            self::Active => self::Blocked,
            self::Blocked => self::Active,
        };
    }

    public function transitionAbility(): string
    {
        return match ($this) {
            self::Active => 'unblock',
            self::Blocked => 'block',
        };
    }

    public function transitionMessage(): string
    {
        return match ($this) {
            self::Active => __('User unblocked successfully.'),
            self::Blocked => __('User blocked successfully.'),
        };
    }
}
