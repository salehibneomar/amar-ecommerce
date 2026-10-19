<?php

namespace App\Enums\Order;

enum OrderStatusEnum: string
{
    case Requested = 'requested';
    case Acknowledged = 'acknowledged';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Requested => __('Requested'),
            self::Acknowledged => __('Acknowledged'),
            self::Completed => __('Completed'),
            self::Cancelled => __('Cancelled'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Requested => 'blue',
            self::Acknowledged => 'amber',
            self::Completed => 'green',
            self::Cancelled => 'red',
        };
    }

    public function dotClass(): string
    {
        return match ($this->color()) {
            'blue' => 'bg-blue-500',
            'amber' => 'bg-amber-400',
            'green' => 'bg-green-500',
            'red' => 'bg-red-500',
            default => 'bg-zinc-400',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Requested => 'clock',
            self::Acknowledged => 'chat-bubble-left-right',
            self::Completed => 'check-circle',
            self::Cancelled => 'x-circle',
        };
    }

    /**
     * @return array{heading: string, text: string}
     */
    public function notice(): array
    {
        return match ($this) {
            self::Requested => ['heading' => __('We received your order.'), 'text' => __('We will contact you soon to confirm the payment.')],
            self::Acknowledged => ['heading' => __('We are working on your order.'), 'text' => __('Your books unlock as soon as the payment is verified.')],
            self::Completed => ['heading' => __('This order is complete.'), 'text' => __('Your books are ready in your library.')],
            self::Cancelled => ['heading' => __('This order was cancelled.'), 'text' => __('Contact us if you think this is a mistake.')],
        };
    }

    public function isCancelled(): bool
    {
        return $this === self::Cancelled;
    }

    public function isUnresolved(): bool
    {
        return in_array($this, self::unresolved(), true);
    }

    /**
     * @return list<array{label: string, reached: bool, current: bool}>
     */
    public function steps(): array
    {
        $flow = [self::Requested, self::Acknowledged, self::Completed];
        $position = array_search($this, $flow, true);

        return array_map(fn (self $step, int $index): array => [
            'label' => $step->label(),
            'reached' => $position !== false && $index <= $position,
            'current' => $this !== self::Completed && $index === $position,
        ], $flow, array_keys($flow));
    }

    /**
     * @return list<self>
     */
    public static function unresolved(): array
    {
        return [self::Requested, self::Acknowledged];
    }
}
