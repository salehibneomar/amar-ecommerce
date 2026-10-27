<?php

use App\Http\Controllers\Frontend\AccountController;
use Illuminate\Support\Facades\Route;

Route::controller(AccountController::class)
    ->prefix('user')
    ->name('user.')
    ->middleware(['auth', 'verified', 'not-blocked', 'customer'])
    ->group(function () {
        Route::get('dashboard', 'dashboard')->name('dashboard');
        Route::get('profile', 'profile')->name('profile');
        Route::get('settings', 'settings')->name('settings');
        Route::get('notifications', 'notifications')->name('notifications');
        Route::get('orders', 'orders')->name('orders');
        Route::get('orders/{order:public_order_id}', 'orderDetail')->name('orders.detail');
        Route::post('orders/{order:public_order_id}/cancel', 'cancelOrder')->middleware('throttle:10,1')->name('orders.cancel');
        Route::get('orders/{order:public_order_id}/invoice', 'invoice')->name('orders.invoice');
    });
