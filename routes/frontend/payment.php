<?php

use App\Http\Controllers\Frontend\PaymentController;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

Route::controller(PaymentController::class)->prefix('payments')->name('payments.')->group(function () {
    Route::get('checkout', 'checkout')->name('checkout');
    Route::post('checkout', 'place')->middleware('throttle:10,1')->name('place');
    Route::get('{payment:reference}', 'show')->middleware('signed')->name('show');
    Route::get('{payment:reference}/return', 'return')->middleware('throttle:30,1')->name('return');
    Route::post('{payment:reference}/return', 'callback')->middleware('throttle:30,1')->withoutMiddleware([StartSession::class, ShareErrorsFromSession::class, PreventRequestForgery::class, AddQueuedCookiesToResponse::class])->name('callback');
    Route::post('webhook/{gateway}', 'webhook')->name('webhook');
});
