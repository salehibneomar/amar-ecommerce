<?php

namespace App\Models;

use App\Enums\Catalog\CategoryStatusEnum;
use App\Enums\System\FrontendCacheKeyEnum;
use App\Interfaces\Seo\SeoMetaInterface;
use App\Traits\Cache\ClearsFrontendCacheTrait;
use App\Traits\Model\CmsUpdateTrackerTrait;
use App\Traits\Model\MediaTrait;
use App\Traits\Seo\SeoMetaTrait;
use Carbon\CarbonImmutable;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property int|null $parent_id
 * @property int $depth
 * @property string|null $description
 * @property CategoryStatusEnum $status
 * @property int $sort_order
 * @property bool $is_featured
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read string|null $image_thumbnail_url
 * @property-read SeoMeta|null $seoMeta
 */
class Category extends Model implements HasMedia, SeoMetaInterface
{
    /** @use HasFactory<CategoryFactory> */
    use ClearsFrontendCacheTrait, CmsUpdateTrackerTrait, HasFactory, InteractsWithMedia, MediaTrait, SeoMetaTrait, SoftDeletes {
        MediaTrait::registerMediaCollections insteadof InteractsWithMedia;
        MediaTrait::registerMediaConversions insteadof InteractsWithMedia;
    }

    protected $fillable = [
        'name',
        'slug',
        'parent_id',
        'depth',
        'description',
        'status',
        'sort_order',
        'is_featured',
    ];

    /**
     * @return list<string>
     */
    public function mediaConfigPaths(): array
    {
        return [
            'category.media.image',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Category $category): void {
            if ($category->isDirty('name') || blank($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => CategoryStatusEnum::class,
            'sort_order' => 'integer',
            'depth' => 'integer',
            'is_featured' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<self, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<self, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * @return HasMany<Product, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * @return Attribute<string|null, never>
     */
    protected function imageThumbnailUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->thumbnailUrl('category.media.image'));
    }

    public function isParent(): bool
    {
        return $this->parent_id === null;
    }

    /**
     * @return array{title: string|null, description: string|null, image: string|null}
     */
    public function seoMetaDefaults(): array
    {
        return [
            'title' => $this->name,
            'description' => plainText($this->description) ?: null,
            'image' => $this->mediaUrl('category.media.image', 'large'),
        ];
    }

    /**
     * @return list<FrontendCacheKeyEnum>
     */
    public function frontendCacheKeys(): array
    {
        return [FrontendCacheKeyEnum::Categories, FrontendCacheKeyEnum::Products, FrontendCacheKeyEnum::Offers];
    }
}
