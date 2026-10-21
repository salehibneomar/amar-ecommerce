<?php

namespace App\Providers;

use App\Services\Backoffice\System\MaintenanceModeService;
use App\Services\Backoffice\System\SystemSettingService;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance;
use Illuminate\Foundation\MaintenanceModeManager;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View as ViewInstance;

class SystemSettingProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(SystemSettingService::class);
        $this->app->afterResolving(MaintenanceModeManager::class, fn (MaintenanceModeManager $manager): MaintenanceModeManager => $manager->extend('system', fn (Application $app): MaintenanceModeService => $app->make(MaintenanceModeService::class)));
    }

    public function boot(): void
    {
        $this->allowAdminRoutesDuringMaintenance();
        $this->shareSystemSettingsWithViews();
    }

    private function allowAdminRoutesDuringMaintenance(): void
    {
        PreventRequestsDuringMaintenance::except(adminRoutePrefix().'/*');
    }

    private function shareSystemSettingsWithViews(): void
    {
        View::composer(['partials.head', 'components.brand.app-logo', 'components.brand.app-logo-icon'], fn (ViewInstance $view): ViewInstance => $view->with('brandUrls', app(SystemSettingService::class)->brandUrls()));
        View::composer('errors::503', fn (ViewInstance $view): ViewInstance => $view->with('maintenanceEndsAt', app(SystemSettingService::class)->maintenanceEndsAt()));
    }
}
