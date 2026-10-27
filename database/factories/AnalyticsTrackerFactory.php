<?php

namespace Database\Factories;

use App\Enums\System\AnalyticsTrackerProviderEnum;
use App\Models\AnalyticsTracker;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AnalyticsTracker>
 */
class AnalyticsTrackerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'provider' => AnalyticsTrackerProviderEnum::GoogleAnalytics,
            'tracking_id' => 'G-'.strtoupper(fake()->bothify('??##??##??')),
            'is_enabled' => true,
        ];
    }

    public function googleTagManager(): static
    {
        return $this->state(fn (): array => ['provider' => AnalyticsTrackerProviderEnum::GoogleTagManager, 'tracking_id' => 'GTM-'.strtoupper(fake()->bothify('??##??#'))]);
    }

    public function metaPixel(): static
    {
        return $this->state(fn (): array => ['provider' => AnalyticsTrackerProviderEnum::MetaPixel, 'tracking_id' => fake()->numerify('###############')]);
    }

    public function disabled(): static
    {
        return $this->state(fn (): array => ['is_enabled' => false]);
    }
}
