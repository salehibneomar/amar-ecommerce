<?php

namespace App\Enums\Catalog;

enum ProductStatusEnum: string
{
    case InStock = 'in_stock';
    case OutOfStock = 'out_of_stock';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::InStock => __('In Stock'),
            self::OutOfStock => __('Out of Stock'),
            self::Archived => __('Archived'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::InStock => 'green',
            self::OutOfStock => 'red',
            self::Archived => 'gray',
        };
    }

    public function isInStock(): bool
    {
        return $this === self::InStock;
    }
}
