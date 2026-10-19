<?php

namespace App\Models;

use App\Enums\Catalog\BookTypeEnum;
use App\Interfaces\AiAssistant\AiAssistantInterface;
use App\Traits\AiAssistant\ProductAiAssistantTrait;
use Database\Factories\BookFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;

/**
 * @property string|null $author
 * @property int|null $pages
 * @property-read string|null $cover_thumbnail_url
 * @property-read string|null $cover_preview_url
 * @property-read string|null $cover_large_url
 * @property-read bool $has_file
 * @property-read bool $is_previewable
 *
 * @method static BookFactory factory($count = null, $state = [])
 */
class Book extends Product implements AiAssistantInterface
{
    use ProductAiAssistantTrait;

    protected ?string $detailTable = 'books';

    protected array $detailColumns = [
        'author',
        'pages',
    ];

    /**
     * @return list<string>
     */
    public function mediaConfigPaths(): array
    {
        return [
            'book.media.file',
            'book.media.preview',
            'book.media.cover',
        ];
    }

    /**
     * @return list<string>
     */
    protected function aiAssistantColumns(): array
    {
        return ['author'];
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'pages' => 'integer',
        ];
    }

    /**
     * @return Attribute<string|null, never>
     */
    protected function coverThumbnailUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->thumbnailUrl('book.media.cover'));
    }

    /**
     * @return Attribute<string|null, never>
     */
    protected function coverPreviewUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->mediaUrl('book.media.cover', 'regular'));
    }

    /**
     * @return Attribute<string|null, never>
     */
    protected function coverLargeUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->mediaUrl('book.media.cover', 'large'));
    }

    /**
     * @return Attribute<bool, never>
     */
    protected function hasFile(): Attribute
    {
        return Attribute::get(fn (): bool => $this->hasConfiguredMedia('book.media.file'));
    }

    /**
     * @return Attribute<bool, never>
     */
    protected function isPreviewable(): Attribute
    {
        return Attribute::get(fn (): bool => BookTypeEnum::current()->isPhysicalOnly() ? $this->hasConfiguredMedia('book.media.preview') : $this->is_digital && $this->has_file);
    }
}
