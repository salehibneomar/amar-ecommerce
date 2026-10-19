<?php

use App\Livewire\Frontend\Auth\ForgotPassword;
use App\Livewire\Frontend\Auth\ResetPassword as ResetPasswordComponent;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Laravel\Fortify\Features;
use Livewire\Livewire;

function passwordResetToken(User $user, string $broker): string
{
    /** @var PasswordBroker $passwordBroker */
    $passwordBroker = Password::broker($broker);

    return $passwordBroker->createToken($user);
}

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::resetPasswords());
});

test('reset password link screen can be rendered', function () {
    $response = $this->get(route('password.request'));

    $response->assertOk();
});

test('reset password link can be requested', function () {
    Notification::fake();

    $user = User::factory()->superAdmin()->create();

    $this->post(route('password.request'), ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class);
});

test('reset password screen can be rendered', function () {
    Notification::fake();

    $user = User::factory()->superAdmin()->create();

    $this->post(route('password.request'), ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
        $response = $this->get(route('password.reset', ['token' => $notification->token, 'email' => $user->email]));

        $response->assertOk();

        return true;
    });
});

test('password can be reset with valid token', function () {
    Notification::fake();

    $user = User::factory()->superAdmin()->create();

    $this->post(route('password.request'), ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
        $response = $this->post(route('password.update'), [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('login'))
            ->assertSessionHas('success', __('passwords.reset'));

        return true;
    });
});

test('reset links point each user to their own panel', function () {
    Notification::fake();

    $admin = User::factory()->superAdmin()->create();
    $customer = User::factory()->create();

    $this->post(route('password.request'), ['email' => $admin->email]);
    Livewire::test(ForgotPassword::class)->set('email', $customer->email)->call('sendResetLink');

    Notification::assertSentTo($admin, ResetPassword::class, fn ($notification) => str_contains(
        $notification->toMail($admin)->actionUrl, route('password.reset', $notification->token),
    ));
    Notification::assertSentTo($customer, ResetPassword::class, fn ($notification) => str_contains(
        $notification->toMail($customer)->actionUrl, route('user.password.reset', $notification->token),
    ));
});

test('customers can reset their password from the customer panel', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->get(route('user.password.request'))->assertOk()->assertSeeLivewire(ForgotPassword::class);

    Livewire::test(ForgotPassword::class)->set('email', $user->email)->call('sendResetLink')->assertHasNoErrors();

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
        $this->get(route('user.password.reset', ['token' => $notification->token, 'email' => $user->email]))->assertOk();

        Livewire::test(ResetPasswordComponent::class, ['token' => $notification->token])
            ->set('email', $user->email)
            ->set('password', 'new-password')
            ->set('password_confirmation', 'new-password')
            ->call('resetPassword')
            ->assertHasNoErrors()
            ->assertRedirect(route('user.login'));

        expect(session('success'))->toBe(__('passwords.reset'));

        return Hash::check('new-password', $user->refresh()->password);
    });
});

test('an invalid reset token is rejected on the customer panel', function () {
    $user = User::factory()->create();

    Livewire::test(ResetPasswordComponent::class, ['token' => 'invalid-token'])
        ->set('email', $user->email)
        ->set('password', 'new-password')
        ->set('password_confirmation', 'new-password')
        ->call('resetPassword')
        ->assertHasErrors('email');
});

test('expired, used or invalid reset links redirect home with an error toast on both panels', function () {
    $customer = User::factory()->create();
    $admin = User::factory()->superAdmin()->create();
    $customerToken = passwordResetToken($customer, 'users');
    $adminToken = passwordResetToken($admin, 'admins');
    $message = __('This password reset link has expired or is invalid.');

    $this->get(route('user.password.reset', ['token' => 'invalid-token', 'email' => $customer->email]))
        ->assertRedirect(route('home'))
        ->assertSessionHas('error', $message);
    $this->get(route('user.password.reset', ['token' => $adminToken, 'email' => $admin->email]))
        ->assertRedirect(route('home'));

    $this->travel(config('auth.passwords.users.expire') + 1)->minutes();

    $this->get(route('user.password.reset', ['token' => $customerToken, 'email' => $customer->email]))
        ->assertRedirect(route('home'))
        ->assertSessionHas('error', $message);
    $this->get(route('password.reset', ['token' => $adminToken, 'email' => $admin->email]))
        ->assertRedirect(route('home'))
        ->assertSessionHas('error', $message);
});

test('a reset link cannot be opened again after it was used', function () {
    $customer = User::factory()->create();
    $token = passwordResetToken($customer, 'users');

    Livewire::test(ResetPasswordComponent::class, ['token' => $token])
        ->set('email', $customer->email)
        ->set('password', 'new-password')
        ->set('password_confirmation', 'new-password')
        ->call('resetPassword')
        ->assertHasNoErrors();

    $this->get(route('user.password.reset', ['token' => $token, 'email' => $customer->email]))
        ->assertRedirect(route('home'))
        ->assertSessionHas('error', __('This password reset link has expired or is invalid.'));
});

test('opening reset links is rate limited per ip across both panels', function () {
    config(['throttle.password_reset_link' => 2]);

    $customer = User::factory()->create();
    $token = passwordResetToken($customer, 'users');

    $this->get(route('user.password.reset', ['token' => $token, 'email' => $customer->email]))->assertOk();
    $this->get(route('password.reset', ['token' => 'invalid-token', 'email' => $customer->email]))->assertRedirect(route('home'));

    $this->get(route('user.password.reset', ['token' => $token, 'email' => $customer->email]))
        ->assertRedirect(route('home'))
        ->assertSessionHas('error', fn (string $message): bool => str_starts_with($message, 'Too many attempts.'));
});

test('each panel only sends reset links to its own kind of account', function () {
    Notification::fake();

    $admin = User::factory()->superAdmin()->create();
    $customer = User::factory()->create();

    $this->post(route('password.email'), ['email' => $customer->email])->assertSessionHasErrors(['email' => __('passwords.user')]);
    Livewire::test(ForgotPassword::class)->set('email', $admin->email)->call('sendResetLink')->assertHasErrors(['email' => __('passwords.user')]);

    Notification::assertNothingSent();
});

test('a customer reset form cannot reset an administrator password', function () {
    $admin = User::factory()->superAdmin()->create();
    $token = passwordResetToken($admin, 'admins');

    Livewire::test(ResetPasswordComponent::class, ['token' => $token])
        ->set('email', $admin->email)
        ->set('password', 'new-password')
        ->set('password_confirmation', 'new-password')
        ->call('resetPassword')
        ->assertHasErrors('email');

    expect(Hash::check('new-password', $admin->refresh()->password))->toBeFalse();
});

test('reset link requests are throttled per ip across both panels', function () {
    Notification::fake();
    config(['throttle.password_reset' => 12]);

    foreach (range(1, 6) as $attempt) {
        $this->post(route('password.email'), ['email' => "missing{$attempt}@example.com"]);
        Livewire::test(ForgotPassword::class)->set('email', "missing{$attempt}@example.com")->call('sendResetLink');
    }

    $customer = User::factory()->create();

    Livewire::test(ForgotPassword::class)
        ->set('email', $customer->email)
        ->call('sendResetLink')
        ->assertHasErrors('email');

    Notification::assertNothingSent();
});

test('after a reset link is sent the form shows a resend countdown and disables the email field', function () {
    Notification::fake();

    $customer = User::factory()->create();
    $admin = User::factory()->superAdmin()->create();

    Livewire::test(ForgotPassword::class)
        ->set('email', $customer->email)
        ->call('sendResetLink')
        ->assertHasNoErrors()
        ->assertDispatched('toast-show', slots: ['text' => __('passwords.sent')], dataset: ['variant' => 'success'])
        ->assertSet('resendAvailableIn', 300)
        ->assertSeeHtml('data-test="resend-countdown"')
        ->assertSeeHtml('<fieldset class="contents" x-bind:disabled="remaining > 0" disabled>');

    $this->post(route('password.email'), ['email' => $admin->email])->assertSessionHasNoErrors()->assertSessionHas('success', __('passwords.sent'));
    $this->get(route('password.request'))->assertSeeHtml('remaining: 300');
});

test('the same account must wait before another reset link is sent', function () {
    Notification::fake();

    $customer = User::factory()->create();

    Livewire::test(ForgotPassword::class)->set('email', $customer->email)->call('sendResetLink')->assertHasNoErrors();
    Livewire::test(ForgotPassword::class)->set('email', $customer->email)->call('sendResetLink')->assertHasErrors(['email' => __('passwords.throttled')]);

    Notification::assertSentToTimes($customer, ResetPassword::class, 1);
});

test('each panel keeps its own resend countdown', function () {
    Notification::fake();

    $admin = User::factory()->superAdmin()->create();

    $this->post(route('password.email'), ['email' => $admin->email])->assertSessionHasNoErrors();

    $this->get(route('password.request'))->assertSeeHtml('remaining: 300');
    Livewire::test(ForgotPassword::class)->assertSet('resendAvailableIn', 0);
});

test('reset links expire after the configured minutes', function () {
    Notification::fake();

    $customer = User::factory()->create();
    $token = passwordResetToken($customer, 'users');

    $this->travel(config('auth.passwords.users.expire') + 1)->minutes();

    Livewire::test(ResetPasswordComponent::class, ['token' => $token])
        ->set('email', $customer->email)
        ->set('password', 'new-password')
        ->set('password_confirmation', 'new-password')
        ->call('resetPassword')
        ->assertHasErrors(['email' => __('passwords.token')]);

    expect(config('auth.passwords.users.expire'))->toBe(15);
});
