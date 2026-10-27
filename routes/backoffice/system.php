<?php

use App\Enums\User\PermissionEnum;
use App\Http\Controllers\Backoffice\NotificationController;
use App\Http\Controllers\Backoffice\PaymentGatewayController;
use App\Http\Controllers\Backoffice\SeoMetaController;
use App\Http\Controllers\Backoffice\SettingController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'not-blocked', 'backoffice'])->prefix('manage')->name('manage.')->group(function () {
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');

    Route::controller(SeoMetaController::class)
        ->prefix('seo-metas')
        ->name('seo-metas.')
        ->middleware(['can:'.PermissionEnum::ManageSeoMeta->value, 'password.confirm'])
        ->group(function () {
            Route::get('/', 'index')->name('index');
        });
});

Route::controller(SettingController::class)->prefix('settings')->group(function () {
    Route::middleware(['auth', 'not-blocked', 'backoffice'])->group(function () {
        Route::get('/', 'index')->name('settings.index');
        Route::get('profile', 'profile')->name('profile.edit');
    });

    Route::middleware(['auth', 'verified', 'not-blocked', 'backoffice'])->group(function () {
        Route::get('appearance', 'appearance')->name('appearance.edit');
        Route::get('security', 'security')->middleware('password.confirm')->name('security.edit');
    });

    Route::middleware(['auth', 'verified', 'not-blocked', 'backoffice', 'can:'.PermissionEnum::ManageSystem->value])->group(function () {
        Route::get('system', 'system')->middleware('password.confirm')->name('system.edit');
        Route::get('branding', 'branding')->middleware('password.confirm')->name('branding.edit');
    });
});

Route::controller(PaymentGatewayController::class)
    ->prefix('settings')
    ->middleware(['auth', 'verified', 'not-blocked', 'backoffice', 'can:'.PermissionEnum::ManagePaymentGateways->value, 'password.confirm'])
    ->group(function () {
        Route::get('payments', 'edit')->name('payments.edit');
    });
