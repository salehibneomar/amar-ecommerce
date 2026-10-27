<?php

use App\Enums\User\PermissionEnum;
use App\Http\Controllers\Backoffice\AnalyticsTrackerController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'not-blocked', 'backoffice'])->prefix('manage')->name('manage.')->group(function () {
    Route::controller(AnalyticsTrackerController::class)
        ->prefix('analytics-trackers')
        ->name('analytics-trackers.')
        ->middleware(['can:'.PermissionEnum::ManageAnalytics->value, 'password.confirm'])
        ->group(function () {
            Route::get('/', 'index')->name('index');
        });
});
