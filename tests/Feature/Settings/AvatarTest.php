<?php

use App\Livewire\Frontend\Profile\Index as CustomerProfile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('public');
});

test('staff upload a square avatar with small and medium crops', function () {
    $user = User::factory()->superAdmin()->create();

    Livewire::actingAs($user)->test('pages::settings.profile')
        ->set('avatarUpload', UploadedFile::fake()->image('holiday photo.jpg', 900, 600))
        ->assertHasNoErrors();

    $media = $user->refresh()->getFirstMedia('avatar');

    expect($media->file_name)->toStartWith('avatar-')
        ->and($media->hasGeneratedConversion('small'))->toBeTrue()
        ->and($media->hasGeneratedConversion('medium'))->toBeTrue()
        ->and(getimagesize(Storage::disk('public')->path($media->getPathRelativeToRoot('medium')))[0])->toBe(256)
        ->and(getimagesize(Storage::disk('public')->path($media->getPathRelativeToRoot('medium')))[1])->toBe(256)
        ->and($user->avatar_url)->toContain('medium');
});

test('customers replace and remove their avatar', function () {
    $customer = User::factory()->create();

    $component = Livewire::actingAs($customer)->test(CustomerProfile::class)
        ->set('avatarUpload', UploadedFile::fake()->image('a.png', 300, 300))
        ->set('avatarUpload', UploadedFile::fake()->image('b.png', 300, 300));

    expect($customer->refresh()->getMedia('avatar'))->toHaveCount(1);

    $component->call('removeAvatar');

    expect($customer->refresh()->getMedia('avatar'))->toHaveCount(0)
        ->and($customer->avatar_url)->toBeNull();
});

test('avatars larger than 2 MB or not images are rejected', function () {
    $customer = User::factory()->create();

    Livewire::actingAs($customer)->test(CustomerProfile::class)
        ->set('avatarUpload', UploadedFile::fake()->image('big.jpg')->size(3000))
        ->assertHasErrors('avatarUpload')
        ->assertSee(__('Keep your photo under :size MB.', ['size' => 2]))
        ->set('avatarUpload', UploadedFile::fake()->create('notes.pdf', 100, 'application/pdf'))
        ->assertHasErrors('avatarUpload');

    expect($customer->refresh()->getMedia('avatar'))->toHaveCount(0);
});
