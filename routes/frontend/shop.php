<?php

use App\Http\Controllers\Frontend\CartController;
use App\Http\Controllers\Frontend\PageController;
use Illuminate\Support\Facades\Route;

Route::controller(PageController::class)->group(function () {
    Route::get('/', 'landing')->name('home');
    Route::get('shop', 'shop')->name('shop');
    Route::get('shop/{slug}', 'productDetail')->name('shop.product');
    Route::get('faq', 'faq')->name('faq');
    Route::get('about', 'about')->name('about');
    Route::get('contact', 'contact')->name('contact');
    Route::get('terms', 'terms')->name('terms');
    Route::get('privacy', 'privacy')->name('privacy');
    Route::get('refund-policy', 'refundPolicy')->name('refund-policy');
    Route::get('ai-assistant', 'assistant')->name('assistant');
    Route::get('sitemap.xml', 'sitemap')->name('sitemap');
    Route::get('robots.txt', 'robots')->name('robots');
});

Route::controller(CartController::class)->group(function () {
    Route::get('checkout', 'checkout')->name('checkout');
    Route::get('track-order', 'track')->name('orders.track');
    Route::get('order/{order:public_order_id}', 'confirmation')->middleware('signed')->name('orders.confirmation');
});
