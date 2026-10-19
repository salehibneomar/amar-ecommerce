<?php

namespace App\Models;

use App\Enums\Content\BannerStatusEnum;
use App\Enums\System\FrontendCacheKeyEnum;
use App\Traits\Cache\ClearsFrontendCacheTrait;
use App\Traits\Model\CmsUpdateTrackerTrait;
use App\Traits\Model\MediaTrait;
use Carbon\CarbonImmutable;
use Database\Factories\BannerFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * @property int $id
 * @property string $title
 * @property string|null $subtitle
 * @property bool $show_text
 * @property string|null $link
 * @property int $sort_order
 * @property BannerStatusEnum $status
 * @property CarbonImmutable|null $starts_at
 * @property CarbonImmutable|null $ends_at
 * @property-read string|null $image_url
 * @property-read string|null $image_thumbnail_url
 */
class Banner extends Model implements HasMedia
{
    /** @use HasFactory<BannerFactory> */
    use ClearsFrontendCacheTrait, CmsUpdateTrackerTrait, HasFactory, InteractsWithMedia, MediaTrait {
        MediaTrait::registerMediaCollections insteadof InteractsWithMedia;
        MediaTrait::registerMediaConversions insteadof InteractsWithMedia;
    }

    protected $fillable = [
        'title',
        'subtitle',
        'show_text',
        'link',
        'sort_order',
        'status',
        'starts_at',
        'ends_at',
    ];

    /**
     * @return list<string>
     */
    public function mediaConfigPaths(): array
    {
        return [
            'banner.media.image',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'show_text' => 'boolean',
            'status' => BannerStatusEnum::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    /**
     * @return Attribute<string|null, never>
     */
    protected function imageUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->mediaUrl('banner.media.image', 'regular'));
    }

    /**
     * @return Attribute<string|null, never>
     */
    protected function imageThumbnailUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->thumbnailUrl('banner.media.image'));
    }

    public function isCurrentlyActive(): bool
    {
        if ($this->status !== BannerStatusEnum::Active) {
            return false;
        }

        $now = now();

        if ($this->starts_at && $now->lt($this->starts_at)) {
            return false;
        }

        if ($this->ends_at && $now->gt($this->ends_at)) {
            return false;
        }

        return true;
    }

    /**
     * @return list<FrontendCacheKeyEnum>
     */
    public function frontendCacheKeys(): array
    {
        return [FrontendCacheKeyEnum::Banners];
    }
}
