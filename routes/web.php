<?php

use Illuminate\Support\Facades\Route;

require __DIR__.'/frontend/shop.php';
require __DIR__.'/frontend/payment.php';
require __DIR__.'/frontend/auth.php';
require __DIR__.'/frontend/account.php';

Route::prefix(adminRoutePrefix())->group(function () {
    require __DIR__.'/backoffice/sales.php';
    require __DIR__.'/backoffice/catalog.php';
    require __DIR__.'/backoffice/content.php';
    require __DIR__.'/backoffice/people.php';
    require __DIR__.'/backoffice/marketing.php';
    require __DIR__.'/backoffice/system.php';
});
