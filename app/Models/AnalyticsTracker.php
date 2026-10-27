<?php

namespace App\Models;

use App\Enums\System\AnalyticsTrackerProviderEnum;
use App\Enums\System\FrontendCacheKeyEnum;
use App\Traits\Cache\ClearsFrontendCacheTrait;
use Carbon\CarbonImmutable;
use Database\Factories\AnalyticsTrackerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property AnalyticsTrackerProviderEnum $provider
 * @property string|null $tracking_id
 * @property bool $is_enabled
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 */
class AnalyticsTracker extends Model
{
    /** @use HasFactory<AnalyticsTrackerFactory> */
    use ClearsFrontendCacheTrait, HasFactory;

    protected $fillable = [
        'provider',
        'tracking_id',
        'is_enabled',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'provider' => AnalyticsTrackerProviderEnum::class,
            'is_enabled' => 'boolean',
        ];
    }

    /**
     * @return list<FrontendCacheKeyEnum>
     */
    public function frontendCacheKeys(): array
    {
        return [FrontendCacheKeyEnum::AnalyticsTrackers];
    }
}
