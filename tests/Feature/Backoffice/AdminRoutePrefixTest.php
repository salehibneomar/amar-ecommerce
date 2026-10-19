<?php

use App\Models\User;

test('every admin page lives under the admin route prefix', function (string $route) {
    expect(parse_url(route($route), PHP_URL_PATH))->toStartWith('/'.adminRoutePrefix().'/');
})->with(['login', 'password.request', 'dashboard', 'manage.orders.index', 'settings.index', 'system.edit']);

test('admin pages are not reachable without the prefix', function (string $path) {
    $this->actingAs(User::factory()->superAdmin()->create())->get($path)->assertNotFound();
})->with(['/dashboard', '/manage/orders', '/settings']);

test('guests opening an admin page are sent to the admin login', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
    $this->get(route('user.dashboard'))->assertRedirect(route('user.login'));
});
