<?php

use App\Enums\User\UserRoleEnum;
use App\Livewire\Frontend\Auth\Register;
use App\Models\User;
use Livewire\Livewire;

test('registration screen can be rendered', function () {
    $this->get(route('user.register'))->assertOk();
});

test('the admin panel has no registration screen', function () {
    $this->get('/backoffice/register')->assertNotFound();
});

test('new users can register and are shown the check your email page without being logged in', function () {
    Livewire::test(Register::class)
        ->set('name', 'John Doe')
        ->set('email', 'test@example.com')
        ->set('password', '123456')
        ->set('password_confirmation', '123456')
        ->call('register')
        ->assertHasNoErrors()
        ->assertRedirect(route('user.verification.notice'));

    $user = User::where('email', 'test@example.com')->firstOrFail();

    $this->assertGuest();
    expect($user->hasRole(UserRoleEnum::User->value))->toBeTrue()
        ->and($user->hasVerifiedEmail())->toBeFalse();

    $this->get(route('user.verification.notice'))
        ->assertOk()
        ->assertSee(['We sent a verification link to test@example.com.', 'The link works for 15 minutes.', 'remaining: 300'], false);
});

test('registration rejects passwords shorter than six characters', function () {
    Livewire::test(Register::class)
        ->set('name', 'John Doe')
        ->set('email', 'test@example.com')
        ->set('password', 'short')
        ->set('password_confirmation', 'short')
        ->call('register')
        ->assertHasErrors('password');

    $this->assertGuest();
});

test('registration follows the configured minimum password length', function () {
    config(['auth.password_min_length' => 10]);

    Livewire::test(Register::class)
        ->set('name', 'John Doe')
        ->set('email', 'test@example.com')
        ->set('password', '123456789')
        ->set('password_confirmation', '123456789')
        ->call('register')
        ->assertHasErrors('password');

    $this->assertGuest();
});

test('registration attempts are throttled by the configured limit', function () {
    config(['throttle.register' => 2]);

    foreach (range(1, 2) as $attempt) {
        Livewire::test(Register::class)
            ->set('name', 'John Doe')
            ->set('email', 'test@example.com')
            ->set('password', 'short')
            ->set('password_confirmation', 'short')
            ->call('register')
            ->assertHasErrors('password')
            ->assertHasNoErrors('email');
    }

    Livewire::test(Register::class)
        ->set('name', 'John Doe')
        ->set('email', 'test@example.com')
        ->set('password', '123456')
        ->set('password_confirmation', '123456')
        ->call('register')
        ->assertHasErrors('email');

    $this->assertGuest();
});

test('registration still succeeds when the mail server is down', function () {
    config([
        'mail.default' => 'failover',
        'mail.mailers.smtp.host' => '127.0.0.1',
        'mail.mailers.smtp.port' => 1,
        'mail.mailers.smtp.timeout' => 1,
    ]);

    Livewire::test(Register::class)
        ->set('name', 'John Doe')
        ->set('email', 'test@example.com')
        ->set('password', '123456')
        ->set('password_confirmation', '123456')
        ->call('register')
        ->assertHasNoErrors()
        ->assertRedirect(route('user.verification.notice'));

    expect(User::where('email', 'test@example.com')->exists())->toBeTrue();
});
