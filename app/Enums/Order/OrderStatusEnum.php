<?php

namespace App\Enums\Order;

enum OrderStatusEnum: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Shipped = 'shipped';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => __('Pending'),
            self::Processing => __('Processing'),
            self::Shipped => __('Shipped'),
            self::Completed => __('Completed'),
            self::Cancelled => __('Cancelled'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'blue',
            self::Processing => 'amber',
            self::Shipped => 'violet',
            self::Completed => 'green',
            self::Cancelled => 'red',
        };
    }

    public function dotClass(): string
    {
        return match ($this->color()) {
            'blue' => 'bg-blue-500',
            'amber' => 'bg-amber-400',
            'violet' => 'bg-violet-500',
            'green' => 'bg-green-500',
            'red' => 'bg-red-500',
            default => 'bg-zinc-400',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Pending => 'clock',
            self::Processing => 'cog-6-tooth',
            self::Shipped => 'truck',
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
            self::Pending => ['heading' => __('We received your order.'), 'text' => __('We will confirm it soon.')],
            self::Processing => ['heading' => __('We are preparing your order.'), 'text' => __('We will let you know when it ships.')],
            self::Shipped => ['heading' => __('Your order is on the way.'), 'text' => __('It will reach the delivery address soon.')],
            self::Completed => ['heading' => __('This order is complete.'), 'text' => __('Thank you for shopping with us.')],
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
        $flow = self::flow();
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
    public static function flow(): array
    {
        return [self::Pending, self::Processing, self::Shipped, self::Completed];
    }

    /**
     * @return list<self>
     */
    public static function unresolved(): array
    {
        return [self::Pending, self::Processing];
    }
}
