<?php

namespace App\Models;

use App\Enums\Content\OfferTypeEnum;
use App\Enums\System\FrontendCacheKeyEnum;
use App\Traits\Cache\ClearsFrontendCacheTrait;
use App\Traits\Model\CmsUpdateTrackerTrait;
use Carbon\CarbonImmutable;
use Database\Factories\OfferFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * @property int $id
 * @property string $name
 * @property string $headline
 * @property OfferTypeEnum $type
 * @property string|null $description
 * @property int $discount_percentage
 * @property bool $is_active
 * @property CarbonImmutable|null $starts_at
 * @property CarbonImmutable|null $ends_at
 * @property-read Collection<int, Category> $categories
 */
class Offer extends Model
{
    /** @use HasFactory<OfferFactory> */
    use ClearsFrontendCacheTrait, CmsUpdateTrackerTrait, HasFactory;

    protected $fillable = [
        'name',
        'headline',
        'type',
        'description',
        'discount_percentage',
        'is_active',
        'starts_at',
        'ends_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => OfferTypeEnum::class,
            'discount_percentage' => 'integer',
            'is_active' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    /**
     * @return array{name: string, percentage: int, categories: list<string>, amount: int}
     */
    public function discountSummary(int $discountAmount = 0): array
    {
        return [
            'name' => $this->name,
            'percentage' => $this->discount_percentage,
            'categories' => $this->isGlobal() ? [] : array_values($this->categories->map(fn (Category $category): string => $category->name)->all()),
            'amount' => $discountAmount,
        ];
    }

    /**
     * @return MorphToMany<Category, $this>
     */
    public function categories(): MorphToMany
    {
        return $this->morphedByMany(Category::class, 'offerable');
    }

    public function isGlobal(): bool
    {
        return $this->type === OfferTypeEnum::Global;
    }

    /**
     * @param  list<int>  $categoryPath
     */
    public function appliesToCategoryPath(array $categoryPath): bool
    {
        return $this->isGlobal() || array_intersect($categoryPath, $this->categories->modelKeys()) !== [];
    }

    public function isCurrentlyActive(): bool
    {
        if (! $this->is_active) {
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
        return [FrontendCacheKeyEnum::Offers];
    }
}
