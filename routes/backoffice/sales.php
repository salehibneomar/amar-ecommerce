<?php

use App\Enums\User\PermissionEnum;
use App\Http\Controllers\Backoffice\DashboardController;
use App\Http\Controllers\Backoffice\OrderController;
use App\Http\Controllers\Backoffice\SalesReportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'not-blocked', 'backoffice'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::prefix('manage')->name('manage.')->group(function () {
        Route::controller(OrderController::class)
            ->prefix('orders')
            ->name('orders.')
            ->middleware('can:'.PermissionEnum::ManageOrders->value)
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('{order}/invoice', 'invoice')->name('invoice');
            });

        Route::controller(SalesReportController::class)
            ->prefix('sales-reports')
            ->name('sales-reports.')
            ->middleware('can:'.PermissionEnum::ViewReports->value)
            ->group(function () {
                Route::get('/', 'index')->name('index');
            });
    });
});
