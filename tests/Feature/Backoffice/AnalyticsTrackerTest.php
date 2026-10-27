<?php

use App\Enums\System\AnalyticsTrackerProviderEnum;
use App\Enums\User\PermissionEnum;
use App\Livewire\Backoffice\AnalyticsTrackers\Index;
use App\Models\AnalyticsTracker;
use App\Models\User;
use Livewire\Livewire;

test('administrators can save each tracker on its own', function (AnalyticsTrackerProviderEnum $provider, string $input, string $saved) {
    $this->actingAs(User::factory()->superAdmin()->create());

    Livewire::test(Index::class)
        ->set("trackers.{$provider->value}.is_enabled", true)
        ->set("trackers.{$provider->value}.tracking_id", $input)
        ->call('save', $provider->value)
        ->assertHasNoErrors();

    $tracker = AnalyticsTracker::query()->sole();

    expect($tracker->provider)->toBe($provider)
        ->and($tracker->tracking_id)->toBe($saved)
        ->and($tracker->is_enabled)->toBeTrue();
})->with([
    'google tag manager' => [AnalyticsTrackerProviderEnum::GoogleTagManager, ' gtm-abc1234 ', 'GTM-ABC1234'],
    'google analytics' => [AnalyticsTrackerProviderEnum::GoogleAnalytics, 'g-abc123xyz9', 'G-ABC123XYZ9'],
    'meta pixel' => [AnalyticsTrackerProviderEnum::MetaPixel, '123456789012345', '123456789012345'],
]);

test('an enabled tracker needs a valid id for its provider', function (AnalyticsTrackerProviderEnum $provider, string $trackingId, string $rule) {
    $this->actingAs(User::factory()->superAdmin()->create());

    Livewire::test(Index::class)
        ->set("trackers.{$provider->value}.is_enabled", true)
        ->set("trackers.{$provider->value}.tracking_id", $trackingId)
        ->call('save', $provider->value)
        ->assertHasErrors(["trackers.{$provider->value}.tracking_id" => $rule]);

    expect(AnalyticsTracker::query()->exists())->toBeFalse();
})->with([
    'missing id' => [AnalyticsTrackerProviderEnum::GoogleAnalytics, '', 'required_if_accepted'],
    'tag manager id in the analytics field' => [AnalyticsTrackerProviderEnum::GoogleAnalytics, 'GTM-ABC1234', 'regex'],
    'letters in a pixel id' => [AnalyticsTrackerProviderEnum::MetaPixel, 'PIXEL12345', 'regex'],
]);

test('turning a tracker off keeps its id', function () {
    $this->actingAs(User::factory()->superAdmin()->create());
    AnalyticsTracker::factory()->metaPixel()->create(['tracking_id' => '123456789012345']);

    Livewire::test(Index::class)
        ->assertSet('trackers.meta_pixel.tracking_id', '123456789012345')
        ->set('trackers.meta_pixel.is_enabled', false)
        ->call('save', AnalyticsTrackerProviderEnum::MetaPixel->value)
        ->assertHasNoErrors();

    expect(AnalyticsTracker::query()->sole())
        ->is_enabled->toBeFalse()
        ->tracking_id->toBe('123456789012345');
});

test('staff need the analytics permission to open the analytics page', function () {
    $moderator = User::factory()->moderator()->create();

    $this->actingAs($moderator)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('manage.analytics-trackers.index'))
        ->assertRedirect(route('dashboard'));

    $moderator->givePermissionTo(PermissionEnum::ManageAnalytics->value);

    $this->actingAs($moderator)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('manage.analytics-trackers.index'))
        ->assertOk();
});
