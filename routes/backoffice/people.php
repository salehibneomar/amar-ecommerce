<?php

use App\Enums\User\PermissionEnum;
use App\Http\Controllers\Backoffice\RoleController;
use App\Http\Controllers\Backoffice\StaffController;
use App\Http\Controllers\Backoffice\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'not-blocked', 'backoffice'])->prefix('manage')->name('manage.')->group(function () {
    Route::controller(UserController::class)
        ->prefix('users')
        ->name('users.')
        ->middleware('can:'.PermissionEnum::ManageCustomers->value)
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('{user}/block', 'block')->name('block');
            Route::post('{user}/unblock', 'unblock')->name('unblock');
        });

    Route::controller(StaffController::class)
        ->prefix('staff')
        ->name('staff.')
        ->middleware('can:'.PermissionEnum::ManageStaff->value)
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('{member}/payments', 'payments')->whereNumber('member')->name('payments');
            Route::get('{member}/payments/{payment}/invoice', 'paymentInvoice')->whereNumber(['member', 'payment'])->name('payments.invoice');
        });

    Route::controller(RoleController::class)
        ->prefix('roles')
        ->name('roles.')
        ->middleware(['can:'.PermissionEnum::ManageRoles->value, 'password.confirm'])
        ->group(function () {
            Route::get('/', 'index')->name('index');
        });
});
