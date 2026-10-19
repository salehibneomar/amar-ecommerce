<?php

use App\Enums\Content\BannerStatusEnum;
use App\Livewire\Backoffice\Banners\Index;
use App\Models\Banner;
use App\Models\User;
use App\Services\Backoffice\Content\BannerService;
use Livewire\Livewire;

test('banners can be filtered by status', function () {
    $live = Banner::factory()->create();
    $inactive = Banner::factory()->inactive()->create();
    $service = app(BannerService::class);

    expect(collect($service->getBanners(status: BannerStatusEnum::Active)->items())->pluck('id')->all())->toBe([$live->id])
        ->and(collect($service->getBanners(status: BannerStatusEnum::Inactive)->items())->pluck('id')->all())->toBe([$inactive->id])
        ->and($service->getBanners()->total())->toBe(2);
});

test('inactive banners are never shown on the home page', function () {
    Banner::factory()->inactive()->create();

    expect(Banner::query()->sole()->isCurrentlyActive())->toBeFalse();
});

test('the banner status filter ignores unknown values', function () {
    Livewire::actingAs(User::factory()->superAdmin()->create())->test(Index::class)
        ->set('status', 'nope')
        ->assertSet('status', '');
});
