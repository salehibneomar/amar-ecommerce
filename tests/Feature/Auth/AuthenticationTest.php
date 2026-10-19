<?php

use App\Models\User;
use Laravel\Fortify\Features;

test('login screen can be rendered', function () {
    $response = $this->get(route('login'));

    $response->assertOk();
});

test('guest auth pages are never cached so the back button cannot show them after login', function (string $route) {
    expect($this->get(route($route))->assertOk()->headers->get('Cache-Control'))->toContain('no-store');
})->with(['login', 'user.login', 'user.register', 'user.password.request']);

test('authenticated users are redirected away from auth pages', function (string $route, string $redirect) {
    $user = $route === 'login' ? User::factory()->superAdmin()->create() : User::factory()->create();

    $this->actingAs($user)->get(route($route))->assertRedirect(route($redirect));
})->with([['login', 'dashboard'], ['user.login', 'user.dashboard'], ['user.register', 'user.dashboard']]);

test('users can authenticate using the login screen', function () {
    $user = User::factory()->superAdmin()->create();

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => '123456',
    ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
});

test('regular users cannot log in to the admin panel', function () {
    $user = User::factory()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => '123456',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertSessionHasErrorsIn('email');

    $this->assertGuest();
});

test('blocked authenticated users are logged out on their next request', function () {
    $this->actingAs(User::factory()->superAdmin()->blocked()->create())
        ->get(route('dashboard'))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('email');

    $this->assertGuest();

    $this->actingAs(User::factory()->blocked()->create())
        ->get(route('user.dashboard'))
        ->assertRedirect(route('user.login'))
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('users with two factor enabled are redirected to two factor challenge', function () {
    $this->skipUnlessFortifyHas(Features::twoFactorAuthentication());

    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]);

    $user = User::factory()->withTwoFactor()->create();

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => '123456',
    ]);

    $response->assertRedirect(route('two-factor.login'));
    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('logout'));

    $response->assertRedirect(route('home'));

    $this->assertGuest();
});

test('administrators are sent to the admin login after logout', function () {
    $response = $this->actingAs(User::factory()->superAdmin()->create())->post(route('logout'));

    $response->assertRedirect(route('login'));

    $this->assertGuest();
});

test('the admin login follows the configured login attempt limit', function () {
    config(['throttle.login' => 2]);
    $user = User::factory()->admin()->create();

    foreach (range(1, 2) as $attempt) {
        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'wrong-password'])->assertSessionHasErrors('email');
    }

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'wrong-password'])->assertTooManyRequests();
});
