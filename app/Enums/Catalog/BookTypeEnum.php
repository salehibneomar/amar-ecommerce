<?php

namespace App\Enums\Catalog;

enum BookTypeEnum: string
{
    case Both = 'both';
    case Digital = 'digital';
    case Physical = 'physical';

    public static function current(): self
    {
        return self::tryFrom(bookType()) ?? self::Both;
    }

    public function stockType(): ?StockTypeEnum
    {
        return match ($this) {
            self::Both => null,
            self::Digital => StockTypeEnum::Digital,
            self::Physical => StockTypeEnum::Physical,
        };
    }

    /**
     * @return list<StockTypeEnum>
     */
    public function stockTypes(): array
    {
        $stockType = $this->stockType();

        return $stockType ? [$stockType] : StockTypeEnum::cases();
    }

    public function isPhysicalOnly(): bool
    {
        return $this === self::Physical;
    }

    public function pdfMedia(): string
    {
        return $this->isPhysicalOnly() ? 'book.media.preview' : 'book.media.file';
    }
}
