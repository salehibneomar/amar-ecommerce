<?php

use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\Frontend\AuthController;
use Illuminate\Support\Facades\Route;

Route::controller(AuthController::class)
    ->prefix('user')
    ->name('user.')
    ->group(function () {
        Route::middleware('guest')->group(function () {
            Route::get('login', 'login')->name('login');
            Route::get('register', 'register')->name('register');
            Route::get('forgot-password', 'forgotPassword')->name('password.request');
            Route::get('reset-password/{token}', 'resetPassword')->name('password.reset');
            Route::get('verify-email', 'verifyEmailNotice')->name('verification.notice');
        });

        Route::get('verify-email/{id}/{hash}', 'verifyEmail')
            ->middleware('throttle:verification')
            ->whereNumber('id')
            ->name('verification.verify');
    });

Route::middleware('guest')
    ->controller(SocialAuthController::class)
    ->prefix('auth/{provider}')
    ->name('social.')
    ->group(function () {
        Route::get('redirect', 'redirect')->name('redirect');
        Route::get('callback', 'callback')->name('callback');
    });
