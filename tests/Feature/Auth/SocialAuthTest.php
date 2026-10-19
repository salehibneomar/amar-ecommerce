<?php

use App\Enums\User\UserRoleEnum;
use App\Models\User;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

function configureSocialProviders(): void
{
    foreach (['google', 'facebook'] as $provider) {
        config([
            "services.{$provider}.client_id" => 'client-id',
            "services.{$provider}.client_secret" => 'client-secret',
        ]);
    }
}

function fakeSocialUser(string $provider, string $id = 'social-123', ?string $email = 'jane@example.com'): void
{
    Socialite::fake($provider, (new SocialiteUser)->map([
        'id' => $id,
        'name' => 'Jane Doe',
        'email' => $email,
    ]));
}

test('social buttons are always shown on the customer auth pages only', function () {
    config(['services.google.client_id' => null, 'services.facebook.client_id' => null]);

    $this->get(route('user.login'))
        ->assertOk()
        ->assertSee(route('social.redirect', 'google'))
        ->assertSee(route('social.redirect', 'facebook'));

    $this->get(route('user.register'))
        ->assertSee(route('social.redirect', 'google'));

    $this->get(route('login'))
        ->assertDontSee(route('social.redirect', 'google'));
});

test('unconfigured providers return to the login page and unknown providers are not available', function () {
    config(['services.google.client_id' => null]);

    $this->get(route('social.redirect', 'google'))
        ->assertRedirect(route('user.login'))
        ->assertSessionHasErrors('email');

    $this->get('/auth/github/redirect')->assertNotFound();
});

test('users are redirected to the provider', function () {
    configureSocialProviders();
    Socialite::fake('google');

    $this->get(route('social.redirect', 'google'))
        ->assertRedirect('https://socialite.fake/google/authorize');
});

test('a new user is registered and logged in from the provider callback', function (string $provider) {
    configureSocialProviders();
    fakeSocialUser($provider);

    $this->get(route('social.callback', $provider))
        ->assertRedirect(route('user.dashboard'));

    $user = User::where('email', 'jane@example.com')->firstOrFail();

    $this->assertAuthenticatedAs($user);
    expect($user->{"{$provider}_id"})->toBe('social-123')
        ->and($user->hasPassword())->toBeFalse()
        ->and($user->hasVerifiedEmail())->toBeTrue()
        ->and($user->hasRole(UserRoleEnum::User->value))->toBeTrue();
})->with(['google', 'facebook']);

test('an existing account is linked by email and keeps its password', function () {
    configureSocialProviders();
    $user = User::factory()->create(['email' => 'jane@example.com']);
    fakeSocialUser('google');

    $this->get(route('social.callback', 'google'))->assertRedirect(route('user.dashboard'));

    $this->assertAuthenticatedAs($user);
    expect($user->refresh()->google_id)->toBe('social-123')
        ->and($user->hasPassword())->toBeTrue()
        ->and(User::count())->toBe(1);
});

test('blocked users cannot log in with a provider', function () {
    configureSocialProviders();
    User::factory()->blocked()->create(['email' => 'jane@example.com']);
    fakeSocialUser('google');

    $this->get(route('social.callback', 'google'))
        ->assertRedirect(route('user.login'))
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('administrators cannot log in with a provider', function () {
    configureSocialProviders();
    $admin = User::factory()->superAdmin()->create(['email' => 'jane@example.com']);
    fakeSocialUser('google');

    $this->get(route('social.callback', 'google'))
        ->assertRedirect(route('user.login'))
        ->assertSessionHasErrors('email');

    $this->assertGuest();
    expect($admin->refresh()->google_id)->toBeNull();
});

test('provider accounts without an email are rejected', function () {
    configureSocialProviders();
    fakeSocialUser('facebook', email: null);

    $this->get(route('social.callback', 'facebook'))
        ->assertRedirect(route('user.login'))
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('a declined or failed provider login returns to the login page', function () {
    configureSocialProviders();
    Socialite::fake('google', fn () => throw new RuntimeException('access_denied'));

    $this->get(route('social.callback', 'google'))
        ->assertRedirect(route('user.login'))
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});
