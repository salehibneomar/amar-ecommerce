<?php

namespace App\Services\Backoffice\System;

use App\Enums\System\AnalyticsTrackerProviderEnum;
use App\Enums\System\FrontendCacheKeyEnum;
use App\Models\AnalyticsTracker;
use App\Traits\Cache\FrontendCacheTrait;

class AnalyticsTrackerService
{
    use FrontendCacheTrait;

    /**
     * @var array<int, array{component: string, tracking_id: string}>|null
     */
    private ?array $activeTrackers = null;

    /**
     * @return array<string, array{is_enabled: bool, tracking_id: string}>
     */
    public function trackers(): array
    {
        $saved = AnalyticsTracker::query()->get(['provider', 'tracking_id', 'is_enabled'])->keyBy(fn (AnalyticsTracker $tracker): string => $tracker->provider->value);

        $trackers = [];

        foreach (AnalyticsTrackerProviderEnum::cases() as $provider) {
            $tracker = $saved->get($provider->value);
            $trackers[$provider->value] = [
                'is_enabled' => (bool) $tracker?->is_enabled,
                'tracking_id' => (string) $tracker?->tracking_id,
            ];
        }

        return $trackers;
    }

    public function save(AnalyticsTrackerProviderEnum $provider, bool $enabled, ?string $trackingId): void
    {
        AnalyticsTracker::query()->updateOrCreate(['provider' => $provider], ['is_enabled' => $enabled, 'tracking_id' => $trackingId]);

        $this->activeTrackers = null;
    }

    public function isTracking(): bool
    {
        return $this->activeTrackers() !== [];
    }

    /**
     * @return array<int, array{component: string, tracking_id: string}>
     */
    public function activeTrackers(): array
    {
        return $this->activeTrackers ??= $this->rememberFrontendCache(FrontendCacheKeyEnum::AnalyticsTrackers, fn (): array => AnalyticsTracker::query()
            ->where('is_enabled', true)
            ->whereNotNull('tracking_id')
            ->orderBy('id')
            ->get(['provider', 'tracking_id'])
            ->map(fn (AnalyticsTracker $tracker): array => ['component' => $tracker->provider->component(), 'tracking_id' => (string) $tracker->tracking_id])
            ->values()
            ->all());
    }
}
