<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Fortify\Features;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * @return array<class-string, class-string>
     */
    protected function setUpTraits(): array
    {
        if (! $this->app->environment('testing') || $this->app->configurationIsCached()) {
            throw new RuntimeException('Tests must run in the testing environment without cached config. Run `php artisan config:clear` first.');
        }

        return parent::setUpTraits();
    }

    protected function skipUnlessFortifyHas(string $feature, ?string $message = null): void
    {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
        }
    }
}
