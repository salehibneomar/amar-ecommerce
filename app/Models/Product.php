<?php

namespace App\Models;

use App\Enums\Catalog\ProductBadgeEnum;
use App\Enums\Catalog\ProductStatusEnum;
use App\Enums\System\FrontendCacheKeyEnum;
use App\Interfaces\AiAssistant\AiAssistantInterface;
use App\Traits\AiAssistant\ProductAiAssistantTrait;
use App\Traits\Cache\ClearsFrontendCacheTrait;
use App\Traits\Model\CmsUpdateTrackerTrait;
use App\Traits\Model\MediaTrait;
use Carbon\CarbonImmutable;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * @property int $id
 * @property int|null $category_id
 * @property string $sku
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property int $price
 * @property int|null $discount_percentage
 * @property int $quantity
 * @property ProductStatusEnum $status
 * @property ProductBadgeEnum|null $badge
 * @property bool $is_featured
 * @property string|null $type
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read int $final_price
 * @property-read bool $has_discount
 * @property-read bool $has_any_media
 * @property-read string|null $thumbnail_url
 * @property-read string|null $image_url
 * @property-read string|null $large_image_url
 * @property-read list<array{id: non-negative-int, thumbnail: string, large: string}> $gallery_images
 * @property-read Category|null $category
 */
class Product extends Model implements AiAssistantInterface, HasMedia
{
    /** @use HasFactory<ProductFactory<static>> */
    use ClearsFrontendCacheTrait, CmsUpdateTrackerTrait, HasFactory, InteractsWithMedia, MediaTrait, ProductAiAssistantTrait, SoftDeletes {
        MediaTrait::registerMediaCollections insteadof InteractsWithMedia;
        MediaTrait::registerMediaConversions insteadof InteractsWithMedia;
    }

    protected $table = 'products';

    protected $fillable = [
        'category_id',
        'sku',
        'name',
        'slug',
        'description',
        'price',
        'discount_percentage',
        'quantity',
        'status',
        'badge',
        'is_featured',
        'type',
    ];

    protected ?string $detailTable = null;

    /**
     * @var list<string>
     */
    protected array $detailColumns = [];

    /**
     * @var array<string, mixed>
     */
    private array $detailAttributes = [];

    /**
     * @var array<string, mixed>
     */
    private array $dirtyDetailAttributes = [];

    /**
     * @return list<string>
     */
    public function mediaConfigPaths(): array
    {
        return ['product.media.images'];
    }

    /**
     * @return list<string>
     */
    public function getFillable(): array
    {
        return [...$this->fillable, ...$this->detailColumns];
    }

    /**
     * @return Builder<static>
     */
    public function newQueryWithoutScopes(): Builder
    {
        $query = $this->newModelQuery()->with($this->with)->withCount($this->withCount);

        if ($this->detailTable === null) {
            return $query;
        }

        $table = $this->detailTable;
        $columns = array_map(fn (string $column): string => "{$table}.{$column}", $this->detailColumns);
        $allColumns = $this->qualifyColumn('*');

        $query->leftJoin($table, "{$table}.{$this->getForeignKey()}", '=', $this->getQualifiedKeyName())
            ->getQuery()
            ->beforeQuery(function (QueryBuilder $base) use ($columns, $allColumns): void {
                if ($base->columns === null || $base->columns === ['*']) {
                    $base->columns = [$allColumns];
                }

                if (in_array($allColumns, $base->columns, true)) {
                    $base->addSelect(array_values(array_filter($columns, fn (string $column): bool => ! in_array($column, $base->columns, true))));
                }
            });

        return $query;
    }

    /**
     * @param  array<string, mixed>|object  $attributes
     * @param  string|null  $connection
     */
    public function newFromBuilder($attributes = [], $connection = null): static
    {
        $attributes = (array) $attributes;
        $class = __NAMESPACE__.'\\'.Str::studly((string) ($attributes['type'] ?? ''));

        if (! is_subclass_of($class, static::class)) {
            return parent::newFromBuilder($attributes, $connection);
        }

        /** @var static */
        return (new $class)->newFromBuilder($attributes, $connection);
    }

    public function productType(): ?string
    {
        return static::class === self::class ? null : Str::snake(class_basename(static::class));
    }

    public function getForeignKey(): string
    {
        return 'product_'.$this->getKeyName();
    }

    protected static function booted(): void
    {
        static::addGlobalScope('type', function (Builder $query): void {
            $type = $query->getModel()->productType();

            if ($type !== null) {
                $query->where($query->qualifyColumn('type'), $type);
            }
        });

        static::saving(function (Product $product): void {
            $product->type ??= $product->productType();
        });

        static::saving(fn (Product $product) => $product->stashDetailAttributes());
        static::saved(fn (Product $product) => $product->saveDetailAttributes());

        static::saving(function (Product $product): void {
            if (! $product->isDirty('name') && filled($product->slug)) {
                return;
            }

            $base = Str::slug($product->name) ?: ($product->type ?? 'product');
            $slug = $base;
            $suffix = 2;

            while (self::withTrashed()->where('slug', $slug)->whereKeyNot($product->getKey())->exists()) {
                $slug = $base.'-'.$suffix++;
            }

            $product->slug = $slug;
        });
    }

    private function stashDetailAttributes(): void
    {
        $columns = array_flip($this->detailColumns);
        $this->dirtyDetailAttributes = array_intersect_key($this->getDirty(), $columns);
        $this->detailAttributes = array_intersect_key($this->attributes, $columns);
        $this->attributes = array_diff_key($this->attributes, $columns);
    }

    private function saveDetailAttributes(): void
    {
        if ($this->detailTable === null) {
            return;
        }

        $this->attributes = [...$this->attributes, ...$this->detailAttributes];

        if ($this->wasRecentlyCreated || $this->dirtyDetailAttributes !== []) {
            $this->getConnection()->table($this->detailTable)->updateOrInsert(
                [$this->getForeignKey() => $this->getKey()],
                $this->dirtyDetailAttributes,
            );
        }
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'discount_percentage' => 'integer',
            'quantity' => 'integer',
            'status' => ProductStatusEnum::class,
            'badge' => ProductBadgeEnum::class,
            'is_featured' => 'boolean',
        ];
    }

    /**
     * @return Attribute<int, never>
     */
    protected function finalPrice(): Attribute
    {
        return Attribute::get(function (): int {
            if ($this->discount_percentage) {
                return (int) round($this->price * (100 - $this->discount_percentage) / 100);
            }

            return $this->price;
        });
    }

    /**
     * @return Attribute<bool, never>
     */
    protected function hasDiscount(): Attribute
    {
        return Attribute::get(fn (): bool => (int) $this->discount_percentage > 0);
    }

    /**
     * @return Attribute<bool, never>
     */
    protected function hasAnyMedia(): Attribute
    {
        return Attribute::get(fn (): bool => $this->hasAnyConfiguredMedia());
    }

    /**
     * @return Attribute<string|null, never>
     */
    protected function thumbnailUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->mediaUrl('product.media.images', 'small'));
    }

    /**
     * @return Attribute<string|null, never>
     */
    protected function imageUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->mediaUrl('product.media.images', 'regular'));
    }

    /**
     * @return Attribute<string|null, never>
     */
    protected function largeImageUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->mediaUrl('product.media.images', 'large'));
    }

    /**
     * @return Attribute<list<array{id: non-negative-int, thumbnail: string, large: string}>, never>
     */
    protected function galleryImages(): Attribute
    {
        return Attribute::get(fn (): array => array_values($this->configuredMediaItems('product.media.images')
            ->map(fn (Media $media): array => [
                'id' => $media->id,
                'thumbnail' => $this->mediaItemUrl($media, 'small'),
                'large' => $this->mediaItemUrl($media, 'large'),
            ])
            ->all()));
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * @return list<FrontendCacheKeyEnum>
     */
    public function frontendCacheKeys(): array
    {
        return [FrontendCacheKeyEnum::Products];
    }

    /**
     * @return list<string>
     */
    protected function frontendCacheIgnoredAttributes(): array
    {
        return ['updated_at', 'updated_by', 'quantity'];
    }
}
