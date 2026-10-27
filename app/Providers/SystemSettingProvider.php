<?php

namespace App\Providers;

use App\Services\Backoffice\System\AnalyticsTrackerService;
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
        $this->app->scoped(AnalyticsTrackerService::class);
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
        View::composer(['components.layouts.head', 'components.brand.app-logo', 'components.brand.app-logo-icon'], fn (ViewInstance $view): ViewInstance => $view->with('brandUrls', app(SystemSettingService::class)->brandUrls()));
        View::composer(['components.brand.theme-colors', 'components.layouts.head', 'mail::themes.brand'], fn (ViewInstance $view): ViewInstance => $view->with('theme', app(SystemSettingService::class)->theme()));
        View::composer('components.analytics.scripts', fn (ViewInstance $view): ViewInstance => $view->with('analyticsTrackers', app(AnalyticsTrackerService::class)->activeTrackers()));
        View::composer('errors::503', fn (ViewInstance $view): ViewInstance => $view->with('maintenanceEndsAt', app(SystemSettingService::class)->maintenanceEndsAt()));
    }
}
