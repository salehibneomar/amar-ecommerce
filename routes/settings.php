<?php

use App\Enums\User\PermissionEnum;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'not-blocked', 'backoffice'])->group(function () {
    Route::livewire('settings', 'pages::settings.index')->name('settings.index');

    Route::livewire('settings/profile', 'pages::settings.profile')->name('profile.edit');
});

Route::middleware(['auth', 'verified', 'not-blocked', 'backoffice'])->group(function () {
    Route::livewire('settings/appearance', 'pages::settings.appearance')->name('appearance.edit');

    Route::livewire('settings/security', 'pages::settings.security')
        ->middleware([
            'password.confirm',
        ])
        ->name('security.edit');

});

Route::middleware(['auth', 'verified', 'not-blocked', 'backoffice', 'can:'.PermissionEnum::ManageSystem->value])->group(function () {
    Route::livewire('settings/system', 'pages::settings.system')
        ->middleware([
            'password.confirm',
        ])
        ->name('system.edit');
});
