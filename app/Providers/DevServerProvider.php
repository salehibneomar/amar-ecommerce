<?php

namespace App\Providers;

use Illuminate\Foundation\DevCommands;
use Illuminate\Support\ServiceProvider;

class DevServerProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->configureDevServer();
    }

    private function configureDevServer(): void
    {
        DevCommands::artisan('serve --host='.appServerHost().' --port='.appServerPort(), 'server');
    }
}
