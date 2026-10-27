<?php

namespace App\Enums\Catalog;

enum ProductStatusEnum: string
{
    case Draft = 'draft';
    case InStock = 'in_stock';
    case OutOfStock = 'out_of_stock';
    case Discontinued = 'discontinued';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => __('Draft'),
            self::InStock => __('In Stock'),
            self::OutOfStock => __('Out of Stock'),
            self::Discontinued => __('Discontinued'),
            self::Archived => __('Archived'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'yellow',
            self::InStock => 'green',
            self::OutOfStock => 'red',
            self::Discontinued => 'orange',
            self::Archived => 'gray',
        };
    }

    /**
     * @return list<self>
     */
    public static function listed(): array
    {
        return [self::InStock, self::OutOfStock];
    }

    public function isInStock(): bool
    {
        return $this === self::InStock;
    }
}
