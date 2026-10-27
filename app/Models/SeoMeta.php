<?php

namespace App\Models;

use App\Enums\System\FrontendCacheKeyEnum;
use App\Interfaces\Seo\SeoMetaInterface;
use App\Traits\Cache\ClearsFrontendCacheTrait;
use App\Traits\Model\MediaTrait;
use Carbon\CarbonImmutable;
use Database\Factories\SeoMetaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * @property int $id
 * @property string $seoable_type
 * @property int $seoable_id
 * @property string|null $title
 * @property string|null $description
 * @property bool $noindex
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property-read Model|null $seoable
 */
class SeoMeta extends Model implements HasMedia
{
    /** @use HasFactory<SeoMetaFactory> */
    use ClearsFrontendCacheTrait, HasFactory, InteractsWithMedia, MediaTrait {
        MediaTrait::registerMediaCollections insteadof InteractsWithMedia;
        MediaTrait::registerMediaConversions insteadof InteractsWithMedia;
    }

    protected $fillable = [
        'title',
        'description',
        'noindex',
    ];

    /**
     * @var list<string>
     */
    protected $touches = ['seoable'];

    /**
     * @return MorphTo<Model, $this>
     */
    public function seoable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return list<string>
     */
    public function mediaConfigPaths(): array
    {
        return ['seo.media.image'];
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'noindex' => 'boolean',
        ];
    }

    /**
     * @return list<FrontendCacheKeyEnum>
     */
    public function frontendCacheKeys(): array
    {
        return $this->seoable instanceof SeoMetaInterface ? $this->seoable->frontendCacheKeys() : [];
    }
}
