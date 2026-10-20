<?php

use App\Enums\User\PermissionEnum;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\Backoffice\BannerController;
use App\Http\Controllers\Backoffice\CategoryController;
use App\Http\Controllers\Backoffice\DashboardController;
use App\Http\Controllers\Backoffice\FaqController;
use App\Http\Controllers\Backoffice\NotificationController;
use App\Http\Controllers\Backoffice\OfferController;
use App\Http\Controllers\Backoffice\OrderController;
use App\Http\Controllers\Backoffice\ProductController;
use App\Http\Controllers\Backoffice\RoleController;
use App\Http\Controllers\Backoffice\SalesReportController;
use App\Http\Controllers\Backoffice\StaffController;
use App\Http\Controllers\Backoffice\UserController;
use App\Http\Controllers\Frontend\AccountController;
use App\Http\Controllers\Frontend\AuthController;
use App\Http\Controllers\Frontend\CartController;
use App\Http\Controllers\Frontend\PageController;
use App\Http\Controllers\Frontend\PaymentController;
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
});

Route::controller(CartController::class)->group(function () {
    Route::get('checkout', 'checkout')->name('checkout');
    Route::get('track-order', 'track')->name('orders.track');
    Route::get('order/{order:public_order_id}', 'confirmation')->middleware('signed')->name('orders.confirmation');
});

Route::controller(PaymentController::class)->prefix('payments')->name('payments.')->group(function () {
    Route::get('checkout', 'checkout')->name('checkout');
    Route::post('checkout', 'place')->middleware('throttle:10,1')->name('place');
    Route::get('{payment:reference}', 'show')->middleware('signed')->name('show');
    Route::get('{payment:reference}/return', 'return')->middleware('throttle:30,1')->name('return');
    Route::post('webhook/{gateway}', 'webhook')->name('webhook');
});

Route::prefix('user')
    ->name('user.')
    ->group(function () {
        Route::middleware('guest')
            ->controller(AuthController::class)
            ->group(function () {
                Route::get('login', 'login')->name('login');
                Route::get('register', 'register')->name('register');
                Route::get('forgot-password', 'forgotPassword')->name('password.request');
                Route::get('reset-password/{token}', 'resetPassword')->name('password.reset');
                Route::get('verify-email', 'verifyEmailNotice')->name('verification.notice');
            });

        Route::get('verify-email/{id}/{hash}', [AuthController::class, 'verifyEmail'])
            ->middleware('throttle:verification')
            ->whereNumber('id')
            ->name('verification.verify');

        Route::middleware(['auth', 'verified', 'not-blocked', 'customer'])->group(function () {
            Route::controller(AccountController::class)->group(function () {
                Route::get('dashboard', 'dashboard')->name('dashboard');
                Route::get('profile', 'profile')->name('profile');
                Route::get('settings', 'settings')->name('settings');
                Route::get('notifications', 'notifications')->name('notifications');
                Route::get('orders', 'orders')->name('orders');
                Route::get('orders/{order:public_order_id}', 'orderDetail')->name('orders.detail');
                Route::post('orders/{order:public_order_id}/cancel', 'cancelOrder')->middleware('throttle:10,1')->name('orders.cancel');
                Route::get('orders/{order:public_order_id}/invoice', 'invoice')->name('orders.invoice');
            });
        });
    });

Route::middleware('guest')
    ->controller(SocialAuthController::class)
    ->prefix('auth/{provider}')
    ->name('social.')
    ->group(function () {
        Route::get('redirect', 'redirect')->name('redirect');
        Route::get('callback', 'callback')->name('callback');
    });

Route::prefix(adminRoutePrefix())->group(function () {
    Route::middleware(['auth', 'verified', 'not-blocked'])->group(function () {
        Route::get('dashboard', [DashboardController::class, 'index'])->middleware('backoffice')->name('dashboard');

        Route::middleware('backoffice')
            ->prefix('manage')
            ->name('manage.')
            ->group(function () {

                Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');

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

                Route::controller(FaqController::class)
                    ->prefix('faqs')
                    ->name('faqs.')
                    ->middleware('can:'.PermissionEnum::ManageFaqs->value)
                    ->group(function () {
                        Route::get('/', 'index')->name('index');
                    });

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
    });

    require __DIR__.'/settings.php';
});
