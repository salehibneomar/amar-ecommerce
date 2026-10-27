<?php

namespace App\Livewire\Backoffice\Settings;

use App\Enums\System\MaintenanceStatusEnum;
use App\Services\Backoffice\System\SystemService;
use App\Services\Backoffice\System\SystemSettingService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Date;
use Livewire\Attributes\Computed;
use Livewire\Component;

class System extends Component
{
    public bool $maintenanceEnabled = false;

    public string $maintenanceStartsAt = '';

    public string $maintenanceEndsAt = '';

    protected SystemService $systemService;

    protected SystemSettingService $systemSettingService;

    public function boot(SystemService $systemService, SystemSettingService $systemSettingService): void
    {
        $this->systemService = $systemService;
        $this->systemSettingService = $systemSettingService;
    }

    public function mount(): void
    {
        $this->maintenanceEnabled = $this->systemSettingService->isMaintenanceEnabled();
        $this->maintenanceStartsAt = (string) $this->systemSettingService->maintenanceStartsAt()?->format('Y-m-d\TH:i');
        $this->maintenanceEndsAt = (string) $this->systemSettingService->maintenanceEndsAt()?->format('Y-m-d\TH:i');
    }

    public function saveMaintenance(): void
    {
        if (! $this->maintenanceEnabled) {
            $this->reset(['maintenanceStartsAt', 'maintenanceEndsAt']);
        }

        $this->validate([
            'maintenanceStartsAt' => ['nullable', 'date'],
            'maintenanceEndsAt' => ['nullable', 'date', 'after:now', ...(filled($this->maintenanceStartsAt) ? ['after:maintenanceStartsAt'] : [])],
        ]);

        $this->systemSettingService->saveMaintenance(
            $this->maintenanceEnabled,
            filled($this->maintenanceStartsAt) ? Date::parse($this->maintenanceStartsAt) : null,
            filled($this->maintenanceEndsAt) ? Date::parse($this->maintenanceEndsAt) : null,
        );

        unset($this->maintenanceStatus, $this->bypassUrl);

        $this->dispatch('toast-show', slots: ['text' => __('Maintenance settings saved.')], dataset: ['variant' => 'success']);
    }

    public function clearCache(): void
    {
        $this->systemService->clearCache();

        session()->flash('success', __('Application cache cleared.'));

        $this->skipRender();
        $this->redirectRoute('system.edit');
    }

    public function optimize(): void
    {
        $this->systemService->optimize();

        session()->flash('success', __('Application optimized.'));

        $this->skipRender();
        $this->redirectRoute('system.edit');
    }

    #[Computed]
    public function maintenanceStatus(): MaintenanceStatusEnum
    {
        return $this->systemSettingService->maintenanceStatus();
    }

    #[Computed]
    public function bypassUrl(): ?string
    {
        $secret = $this->systemSettingService->maintenanceSecret();

        return $secret ? url($secret) : null;
    }

    public function render(): View
    {
        return view('components.backoffice.settings.system');
    }
}
