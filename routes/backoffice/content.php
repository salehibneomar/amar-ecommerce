<?php

use App\Enums\User\PermissionEnum;
use App\Http\Controllers\Backoffice\BannerController;
use App\Http\Controllers\Backoffice\FaqController;
use App\Http\Controllers\Backoffice\OfferController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'not-blocked', 'backoffice'])->prefix('manage')->name('manage.')->group(function () {
    Route::controller(BannerController::class)
        ->prefix('banners')
        ->name('banners.')
        ->middleware('can:'.PermissionEnum::ManageBanners->value)
        ->group(function () {
            Route::get('/', 'index')->name('index');
        });

    Route::controller(OfferController::class)
        ->prefix('offers')
        ->name('offers.')
        ->middleware(['can:'.PermissionEnum::ManageOffers->value, 'password.confirm'])
        ->group(function () {
            Route::get('/', 'index')->name('index');
        });

    Route::controller(FaqController::class)
        ->prefix('faqs')
        ->name('faqs.')
        ->middleware('can:'.PermissionEnum::ManageFaqs->value)
        ->group(function () {
            Route::get('/', 'index')->name('index');
        });
});
