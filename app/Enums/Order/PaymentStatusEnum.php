<?php

namespace App\Enums\Order;

enum PaymentStatusEnum: string
{
    case Paid = 'paid';
    case NotPaid = 'not_paid';
    case Pending = 'pending';

    public function label(): string
    {
        return match ($this) {
            self::Paid => __('Paid'),
            self::NotPaid => __('Not Paid'),
            self::Pending => __('Pending'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Paid => 'green',
            self::NotPaid => 'red',
            self::Pending => 'zinc',
        };
    }

    public function isPending(): bool
    {
        return $this === self::Pending;
    }

    public function isNotPaid(): bool
    {
        return $this === self::NotPaid;
    }
}
