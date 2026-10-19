<?php

use App\Livewire\Frontend\Auth\Login;
use App\Livewire\Frontend\Auth\Register;
use App\Livewire\Frontend\Auth\VerifyEmail as VerifyEmailPage;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Laravel\Fortify\Features;
use Livewire\Livewire;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::emailVerification());
});

test('email verification screen can be rendered', function () {
    $user = User::factory()->unverified()->create();

    $response = $this->actingAs($user)->get(route('verification.notice'));

    $response->assertOk();
});

test('unverified users are redirected to the email verification prompt', function () {
    $user = User::factory()->unverified()->create();

    $response = $this->actingAs($user)->get(route('appearance.edit'));

    $response->assertRedirect(route('verification.notice'));
});

test('email can be verified', function () {
    $user = User::factory()->unverified()->create();

    Event::fake();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)],
    );

    $response = $this->actingAs($user)->get($verificationUrl);

    Event::assertDispatched(Verified::class);

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    $response->assertRedirect(route('user.dashboard', absolute: false).'?verified=1');
});

test('email is not verified with invalid hash', function () {
    $user = User::factory()->unverified()->create();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1('wrong-email')],
    );

    $this->actingAs($user)->get($verificationUrl);

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

test('already verified user visiting verification link is redirected without firing event again', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    Event::fake();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)],
    );

    $this->actingAs($user)->get($verificationUrl)
        ->assertRedirect(route('user.dashboard', absolute: false).'?verified=1');

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    Event::assertNotDispatched(Verified::class);
});

function registerStudent(): User
{
    Livewire::test(Register::class)
        ->set('name', 'New Student')
        ->set('email', 'new@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('register');

    return User::query()->where('email', 'new@example.com')->sole();
}

test('new customers verify from the emailed link while logged out and then log in', function () {
    Notification::fake();
    $this->freezeTime();
    $user = registerStudent();
    $verificationUrl = null;

    Notification::assertSentTo($user, VerifyEmail::class, function (VerifyEmail $notification) use ($user, &$verificationUrl): bool {
        $mail = $notification->toMail($user);
        $verificationUrl = $mail->actionUrl;

        return in_array('This link works for 15 minutes. If it expires, log in again to get a new one.', $mail->outroLines, true);
    });

    parse_str((string) parse_url((string) $verificationUrl, PHP_URL_QUERY), $query);

    expect($verificationUrl)->toStartWith(route('user.verification.verify', ['id' => $user->id, 'hash' => sha1($user->email)]))
        ->and((int) $query['expires'])->toBe(now()->addMinutes(15)->getTimestamp());

    $this->assertGuest();
    $this->get($verificationUrl)
        ->assertRedirect(route('user.login'))
        ->assertSessionHas('success', 'Your email is verified. You can log in now.');

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    $this->assertGuest();

    Livewire::test(Login::class)
        ->set('email', 'new@example.com')
        ->set('password', 'password')
        ->call('login')
        ->assertRedirect(route('user.dashboard'));
});

test('a used verification link shows an already verified info toast until it expires, then an invalid link toast', function () {
    Notification::fake();
    Event::fake([Verified::class]);
    $this->freezeTime();
    $user = registerStudent();
    $verificationUrl = null;

    Notification::assertSentTo($user, VerifyEmail::class, function (VerifyEmail $notification) use ($user, &$verificationUrl): bool {
        $verificationUrl = $notification->toMail($user)->actionUrl;

        return true;
    });

    $this->get((string) $verificationUrl)->assertSessionHas('success', 'Your email is verified. You can log in now.');

    $this->get((string) $verificationUrl)
        ->assertRedirect(route('user.login'))
        ->assertSessionHas('info', 'Your email is already verified. You can log in now.')
        ->assertSessionMissing('success');

    $this->actingAs($user->fresh())->get((string) $verificationUrl)
        ->assertRedirect(route('user.dashboard', ['verified' => 1]))
        ->assertSessionHas('info', 'Your email is already verified.');

    $this->travel(emailVerificationExpireMinutes() + 1)->minutes();

    $this->get((string) $verificationUrl)
        ->assertRedirect(route('user.dashboard'))
        ->assertSessionHas('error', 'This verification link has expired or is invalid.');

    Event::assertDispatchedTimes(Verified::class, 1);
});

test('a still valid link shows an invalid link toast once the email was verified longer ago than the expiry', function () {
    $user = User::factory()->create(['email_verified_at' => now()->subMinutes(emailVerificationExpireMinutes() + 1)]);

    $verificationUrl = URL::temporarySignedRoute('user.verification.verify', now()->addMinutes(emailVerificationExpireMinutes()), ['id' => $user->id, 'hash' => sha1($user->email)]);

    $this->get($verificationUrl)
        ->assertRedirect(route('user.login'))
        ->assertSessionHas('error', 'This verification link has expired or is invalid.')
        ->assertSessionMissing('info');
});

test('expired or tampered verification links send the student back to log in', function () {
    Notification::fake();
    $user = registerStudent();
    $verificationUrl = null;

    Notification::assertSentTo($user, VerifyEmail::class, function (VerifyEmail $notification) use ($user, &$verificationUrl): bool {
        $verificationUrl = $notification->toMail($user)->actionUrl;

        return true;
    });

    $this->travel(16)->minutes();

    $this->get($verificationUrl)
        ->assertRedirect(route('user.login'))
        ->assertSessionHas('error', 'This verification link has expired or is invalid.');

    $this->get(route('user.verification.verify', ['id' => $user->id, 'hash' => sha1('someone@else.com')]))->assertRedirect(route('user.login'));

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

test('unverified students who log in are not logged in and get a new email every time', function () {
    Notification::fake();
    $user = User::factory()->unverified()->create(['email' => 'late@example.com']);

    foreach ([1, 2] as $times) {
        Livewire::test(Login::class)
            ->set('email', 'late@example.com')
            ->set('password', '123456')
            ->call('login')
            ->assertRedirect(route('user.verification.notice'));

        $this->assertGuest();
        Notification::assertSentToTimes($user, VerifyEmail::class, $times);
    }

    $this->get(route('user.verification.notice'))->assertOk()->assertSee('We sent a verification link to late@example.com.');
});

test('the verification page sends guests without a pending verification to log in', function () {
    $this->get(route('user.verification.notice'))->assertRedirect(route('user.login'));
});

test('administrators are sent to the admin dashboard after verifying', function () {
    $admin = User::factory()->superAdmin()->unverified()->create();

    $verificationUrl = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), ['id' => $admin->id, 'hash' => sha1($admin->email)]);

    $this->actingAs($admin)->get($verificationUrl)->assertRedirect(route('dashboard', absolute: false).'?verified=1');
});

test('resending the verification email is limited to the configured attempts per minute', function () {
    Notification::fake();
    config(['throttle.verification_email' => 2]);
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)->post(route('verification.send'))->assertRedirect();
    $this->actingAs($user)->post(route('verification.send'))->assertRedirect();
    $this->actingAs($user)->post(route('verification.send'))->assertTooManyRequests();

    Notification::assertSentToTimes($user, VerifyEmail::class, 2);
});

test('the resend button waits five minutes after each email', function () {
    Notification::fake();
    $this->freezeTime();
    $user = registerStudent();

    Livewire::test(VerifyEmailPage::class)
        ->assertSee('remaining: 300', false)
        ->call('resend');

    Notification::assertSentToTimes($user, VerifyEmail::class, 1);

    $this->travel(301)->seconds();

    Livewire::test(VerifyEmailPage::class)
        ->assertSee('remaining: 0', false)
        ->call('resend')
        ->assertDispatched('toast-show', slots: ['text' => 'A new verification link has been sent to your email.'], dataset: ['variant' => 'success'])
        ->assertSee('remaining: 300', false);

    Notification::assertSentToTimes($user, VerifyEmail::class, 2);
    $this->assertGuest();
});

test('resending from the verification page is limited to the configured attempts per minute', function () {
    Notification::fake();
    config(['throttle.verification_email' => 1]);
    $user = registerStudent();

    $this->travel(301)->seconds();
    Livewire::test(VerifyEmailPage::class)->call('resend')->assertHasNoErrors();

    $this->travel(301)->seconds();
    Livewire::test(VerifyEmailPage::class)->call('resend')->assertHasNoErrors();

    session()->put('email_verification.available_at', 0);
    Livewire::test(VerifyEmailPage::class)->call('resend')->assertHasErrors('email');

    Notification::assertSentToTimes($user, VerifyEmail::class, 3);
});

test('a student who already verified is sent from the verification page to log in', function () {
    Notification::fake();
    $user = registerStudent();
    $user->markEmailAsVerified();

    Livewire::test(VerifyEmailPage::class)->assertRedirect(route('user.login'));
});

test('the verification page polls only while the emailed link can still be used', function () {
    Notification::fake();
    $this->freezeTime();
    registerStudent();

    Livewire::test(VerifyEmailPage::class)->assertSeeHtml('data-test="verification-poll"');

    $this->travel(emailVerificationExpireMinutes())->minutes();

    Livewire::test(VerifyEmailPage::class)
        ->assertDontSeeHtml('data-test="verification-poll"')
        ->assertSeeHtml('visibilitychange');
});

test('the verification page moves to the home page once the student verifies or logs in', function () {
    Notification::fake();
    $user = registerStudent();

    $page = Livewire::test(VerifyEmailPage::class)->call('checkStatus')->assertNoRedirect();

    $user->markEmailAsVerified();

    $page->call('checkStatus')->assertRedirect(route('home'));

    expect(session('success'))->toBe('Your email is verified. You can log in now.');
});
