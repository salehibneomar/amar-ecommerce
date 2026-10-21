<?php

use App\Providers\AccessProvider;
use App\Providers\AppServiceProvider;
use App\Providers\AuthMailProvider;
use App\Providers\DevServerProvider;
use App\Providers\FortifyServiceProvider;
use App\Providers\MediaProvider;
use App\Providers\SystemSettingProvider;

return [
    AppServiceProvider::class,
    AccessProvider::class,
    AuthMailProvider::class,
    MediaProvider::class,
    DevServerProvider::class,
    SystemSettingProvider::class,
    FortifyServiceProvider::class,
];
