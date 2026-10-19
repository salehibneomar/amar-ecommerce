<?php

namespace App\Enums\Order;

enum PaymentMethodEnum: string
{
    case Bkash = 'bkash';
    case Nagad = 'nagad';

    public function label(): string
    {
        return match ($this) {
            self::Bkash => __('bKash'),
            self::Nagad => __('Nagad'),
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return array_column(array_map(fn (self $method): array => [$method->value, $method->label()], self::cases()), 1, 0);
    }
}
