<?php

namespace App\Models;

use App\Enums\System\FrontendCacheKeyEnum;
use App\Traits\Cache\ClearsFrontendCacheTrait;
use App\Traits\Model\MediaTrait;
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
 */
class SystemSetting extends Model implements HasMedia
{
    /** @use HasFactory<SystemSettingFactory> */
    use ClearsFrontendCacheTrait, HasFactory, InteractsWithMedia, MediaTrait {
        MediaTrait::registerMediaCollections insteadof InteractsWithMedia;
        MediaTrait::registerMediaConversions insteadof InteractsWithMedia;
    }

    protected $fillable = [
        'maintenance_enabled',
        'maintenance_starts_at',
        'maintenance_ends_at',
        'maintenance_secret',
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
