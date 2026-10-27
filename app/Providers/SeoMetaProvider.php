<?php

namespace App\Providers;

use App\Services\Backoffice\Seo\SeoMetaService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View as ViewInstance;

class SeoMetaProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(SeoMetaService::class);
    }

    public function boot(): void
    {
        View::composer(['components.layouts.head', 'frontend.robots'], fn (ViewInstance $view): ViewInstance => $view->with('seoMetaSite', app(SeoMetaService::class)->siteDefaults()));
    }
}
