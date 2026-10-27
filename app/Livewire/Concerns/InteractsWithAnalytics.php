<?php

namespace App\Livewire\Concerns;

use App\Services\Frontend\Analytics\AnalyticsEventService;

trait InteractsWithAnalytics
{
    protected AnalyticsEventService $analyticsEventService;

    public function bootInteractsWithAnalytics(AnalyticsEventService $analyticsEventService): void
    {
        $this->analyticsEventService = $analyticsEventService;
    }

    /**
     * @param  array{name: string, params: array<string, mixed>}|null  $event
     */
    protected function trackAnalytics(?array $event): void
    {
        if ($event !== null) {
            $this->dispatch('analytics-event', ...$event);
        }
    }
}
