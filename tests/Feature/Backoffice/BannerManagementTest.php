<?php

use App\Enums\Content\BannerStatusEnum;
use App\Livewire\Backoffice\Banners\Index;
use App\Models\Banner;
use App\Models\User;
use App\Services\Backoffice\Content\BannerService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

test('a banner is created without an image, then its image is uploaded, viewed and removed from the row actions', function () {
    Storage::fake('public');
    $this->actingAs(User::factory()->superAdmin()->create());

    Livewire::test(Index::class)
        ->call('createBanner')
        ->set('title', 'Harvest sale')
        ->call('saveBanner')
        ->assertHasNoErrors()
        ->assertSet('showForm', false);

    $banner = Banner::query()->sole();

    expect($banner->hasConfiguredMedia('banner.media.image'))->toBeFalse();

    Livewire::test(Index::class)
        ->assertDontSeeHtml('wire:click="viewMedia('.$banner->id.')"')
        ->call('uploadMedia', $banner->id)
        ->assertSet('showMediaUpload', true)
        ->set('imageUpload', UploadedFile::fake()->image('harvest.jpg', 1600, 600))
        ->call('saveMedia')
        ->assertHasNoErrors()
        ->assertSet('showMediaUpload', false);

    Livewire::test(Index::class)
        ->assertSeeHtml($banner->refresh()->image_thumbnail_url)
        ->call('viewMedia', $banner->id)
        ->assertSet('showMediaPreview', true)
        ->call('viewImage')
        ->assertSet('showImageViewer', true)
        ->assertSeeHtml($banner->mediaUrl('banner.media.image', 'regular'));

    Livewire::test(Index::class)
        ->call('uploadMedia', $banner->id)
        ->set('removeImage', true)
        ->call('saveMedia')
        ->assertHasNoErrors();

    expect($banner->refresh()->hasConfiguredMedia('banner.media.image'))->toBeFalse();
});

test('a banner image must be an image file', function () {
    Storage::fake('public');
    $this->actingAs(User::factory()->superAdmin()->create());
    $banner = Banner::factory()->create();

    Livewire::test(Index::class)
        ->call('uploadMedia', $banner->id)
        ->set('imageUpload', UploadedFile::fake()->create('notes.pdf', 10, 'application/pdf'))
        ->call('saveMedia')
        ->assertHasErrors('imageUpload');
});
