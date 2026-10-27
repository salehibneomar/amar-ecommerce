<?php

namespace App\Livewire\Backoffice\AnalyticsTrackers;

use App\Enums\System\AnalyticsTrackerProviderEnum;
use App\Services\Backoffice\System\AnalyticsTrackerService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Index extends Component
{
    /**
     * @var array<string, array{is_enabled: bool, tracking_id: string}>
     */
    public array $trackers = [];

    protected AnalyticsTrackerService $analyticsTrackerService;

    public function boot(AnalyticsTrackerService $analyticsTrackerService): void
    {
        $this->analyticsTrackerService = $analyticsTrackerService;
    }

    public function mount(): void
    {
        $this->trackers = $this->analyticsTrackerService->trackers();
    }

    public function save(string $provider): void
    {
        $provider = AnalyticsTrackerProviderEnum::tryFrom($provider);

        if ($provider === null) {
            return;
        }

        $key = 'trackers.'.$provider->value;
        $this->trackers[$provider->value]['tracking_id'] = Str::upper(trim((string) ($this->trackers[$provider->value]['tracking_id'] ?? '')));

        $validated = $this->validate([
            $key.'.is_enabled' => ['boolean'],
            $key.'.tracking_id' => ['nullable', 'required_if_accepted:'.$key.'.is_enabled', 'string', 'regex:'.$provider->pattern()],
        ], [
            $key.'.tracking_id.regex' => __('Enter a valid ID, e.g. :example.', ['example' => $provider->placeholder()]),
        ], [
            $key.'.tracking_id' => __('Tracking ID'),
        ]);

        $tracker = $validated['trackers'][$provider->value];

        $this->analyticsTrackerService->save($provider, (bool) ($tracker['is_enabled'] ?? false), $tracker['tracking_id'] ?: null);

        $this->dispatch('toast-show', slots: ['text' => __(':provider settings saved.', ['provider' => $provider->label()])], dataset: ['variant' => 'success']);
    }

    /**
     * @return list<AnalyticsTrackerProviderEnum>
     */
    #[Computed]
    public function providers(): array
    {
        return AnalyticsTrackerProviderEnum::cases();
    }

    public function render(): View
    {
        return view('components.backoffice.analytics-trackers.index');
    }
}
