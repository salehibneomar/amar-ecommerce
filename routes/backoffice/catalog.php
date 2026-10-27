<?php

use App\Enums\User\PermissionEnum;
use App\Http\Controllers\Backoffice\CategoryController;
use App\Http\Controllers\Backoffice\ProductController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'not-blocked', 'backoffice'])->prefix('manage')->name('manage.')->group(function () {
    Route::controller(CategoryController::class)
        ->prefix('categories')
        ->name('categories.')
        ->middleware('can:'.PermissionEnum::ManageCategories->value)
        ->group(function () {
            Route::get('/', 'index')->name('index');
        });

    Route::controller(ProductController::class)
        ->prefix('products')
        ->name('products.')
        ->middleware('can:'.PermissionEnum::ManageProducts->value)
        ->group(function () {
            Route::get('/', 'index')->name('index');
        });
});
