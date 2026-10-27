<?php

namespace App\Models;

use App\Enums\System\FrontendCacheKeyEnum;
use App\Enums\System\ThemeColorEnum;
use App\Interfaces\Seo\SeoMetaInterface;
use App\Traits\Cache\ClearsFrontendCacheTrait;
use App\Traits\Model\MediaTrait;
use App\Traits\Seo\SeoMetaTrait;
use Carbon\CarbonImmutable;
use Database\Factories\SystemSettingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * @property int $id
 * @property bool $maintenance_enabled
 * @property CarbonImmutable|null $maintenance_starts_at
 * @property CarbonImmutable|null $maintenance_ends_at
 * @property string|null $maintenance_secret
 * @property ThemeColorEnum $theme_color
 * @property bool $seo_indexing_enabled
 * @property string|null $google_site_verification
 * @property-read SeoMeta|null $seoMeta
 */
class SystemSetting extends Model implements HasMedia, SeoMetaInterface
{
    /** @use HasFactory<SystemSettingFactory> */
    use ClearsFrontendCacheTrait, HasFactory, InteractsWithMedia, MediaTrait, SeoMetaTrait {
        MediaTrait::registerMediaCollections insteadof InteractsWithMedia;
        MediaTrait::registerMediaConversions insteadof InteractsWithMedia;
    }

    protected $fillable = [
        'maintenance_enabled',
        'maintenance_starts_at',
        'maintenance_ends_at',
        'maintenance_secret',
        'theme_color',
        'seo_indexing_enabled',
        'google_site_verification',
    ];

    protected $hidden = [
        'maintenance_secret',
    ];

    /**
     * @return list<string>
     */
    public function mediaConfigPaths(): array
    {
        return [
            'system.media.logo',
            'system.media.icon',
            'system.media.favicon',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'maintenance_enabled' => 'boolean',
            'maintenance_starts_at' => 'datetime',
            'maintenance_ends_at' => 'datetime',
            'theme_color' => ThemeColorEnum::class,
            'seo_indexing_enabled' => 'boolean',
        ];
    }

    /**
     * @return list<FrontendCacheKeyEnum>
     */
    public function frontendCacheKeys(): array
    {
        return [FrontendCacheKeyEnum::SystemSettings];
    }
}
