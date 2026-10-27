<?php

use App\Livewire\Frontend\Settings\Index;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

test('customers can open their settings page and guests cannot', function () {
    $this->get(route('user.settings'))->assertRedirect(route('user.login'));

    $this->actingAs(User::factory()->create())
        ->get(route('user.settings'))
        ->assertOk()
        ->assertSeeLivewire('frontend.settings.index');
});

test('customers can change their password with the current one', function () {
    $user = User::factory()->create(['password' => Hash::make('password')]);

    Livewire::actingAs($user)->test(Index::class)
        ->set('current_password', 'password')
        ->set('password', 'new-password')
        ->set('password_confirmation', 'new-password')
        ->call('updatePassword')
        ->assertHasNoErrors()
        ->assertSet('password', '');

    expect(Hash::check('new-password', $user->refresh()->password))->toBeTrue();
});

test('a wrong current password keeps the old password', function () {
    $user = User::factory()->create(['password' => Hash::make('password')]);

    Livewire::actingAs($user)->test(Index::class)
        ->set('current_password', 'wrong-password')
        ->set('password', 'new-password')
        ->set('password_confirmation', 'new-password')
        ->call('updatePassword')
        ->assertHasErrors(['current_password'])
        ->assertSet('password', '');

    expect(Hash::check('password', $user->refresh()->password))->toBeTrue();
});

test('social login customers can set a password without a current one', function () {
    $user = User::factory()->create(['password' => null]);

    Livewire::actingAs($user)->test(Index::class)
        ->set('password', 'new-password')
        ->set('password_confirmation', 'new-password')
        ->call('updatePassword')
        ->assertHasNoErrors();

    expect(Hash::check('new-password', $user->refresh()->password))->toBeTrue();
});
