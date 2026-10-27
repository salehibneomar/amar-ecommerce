<?php

use App\Livewire\Backoffice\Settings\Profile;
use App\Livewire\Frontend\Profile\Index as FrontendProfile;
use App\Models\User;
use Livewire\Livewire;

test('profile page is displayed', function () {
    $this->actingAs($user = User::factory()->superAdmin()->create());

    $this->get(route('profile.edit'))->assertOk();
});

test('profile information can be updated', function () {
    $user = User::factory()->superAdmin()->create();

    $this->actingAs($user);

    $response = Livewire::test(Profile::class)
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->call('updateProfileInformation');

    $response->assertHasNoErrors();

    $user->refresh();

    expect($user->name)->toEqual('Test User');
    expect($user->email)->toEqual('test@example.com');
    expect($user->email_verified_at)->toBeNull();
});

test('email verification status is unchanged when email address is unchanged', function () {
    $user = User::factory()->superAdmin()->create();

    $this->actingAs($user);

    $response = Livewire::test(Profile::class)
        ->set('name', 'Test User')
        ->set('email', $user->email)
        ->call('updateProfileInformation');

    $response->assertHasNoErrors();

    expect($user->refresh()->email_verified_at)->not->toBeNull();
});

test('staff can set and clear their phone number from the profile', function () {
    $user = User::factory()->superAdmin()->create();

    Livewire::actingAs($user)->test(Profile::class)
        ->set('phone', '01712345678')
        ->call('updateProfileInformation')
        ->assertHasNoErrors();

    expect($user->refresh()->phone)->toBe('01712345678');

    Livewire::actingAs($user)->test(Profile::class)
        ->set('phone', '')
        ->call('updateProfileInformation')
        ->assertHasNoErrors();

    expect($user->refresh()->phone)->toBeNull();
});

test('customers can set their phone number from the profile', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)->test(FrontendProfile::class)
        ->set('phone', '01712345678')
        ->call('updateProfile')
        ->assertHasNoErrors();

    expect($user->refresh()->phone)->toBe('01712345678');
});

test('a phone number must have the configured length', function (string $phone) {
    config(['app.valid_phone_length' => 11]);
    $user = User::factory()->create();

    Livewire::actingAs($user)->test(FrontendProfile::class)
        ->set('phone', $phone)
        ->call('updateProfile')
        ->assertHasErrors(['phone' => 'digits']);
})->with(['too short' => '0171234567', 'too long' => '017123456789', 'letters' => '0171234567a']);

test('a phone number already used by another user is rejected but keeping your own is fine', function () {
    User::factory()->create(['phone' => '01712345678']);
    $user = User::factory()->create(['phone' => '01812345678']);

    Livewire::actingAs($user)->test(FrontendProfile::class)
        ->set('phone', '01712345678')
        ->call('updateProfile')
        ->assertHasErrors(['phone' => 'unique']);

    Livewire::actingAs($user)->test(FrontendProfile::class)
        ->call('updateProfile')
        ->assertHasNoErrors();
});
