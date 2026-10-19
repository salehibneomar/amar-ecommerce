<?php

use App\Models\User;

test('super admins see every settings item', function () {
    $this->actingAs(User::factory()->superAdmin()->create())
        ->get(route('settings.index'))
        ->assertOk()
        ->assertSee(route('profile.edit'))
        ->assertSee(route('security.edit'))
        ->assertSee(route('manage.roles.index'))
        ->assertSee(route('system.edit'));
});

test('customers cannot open the settings index', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('settings.index'))
        ->assertRedirect(route('user.dashboard'));
});

test('nested backoffice pages show a back button to their parent page', function () {
    $html = $this->actingAs(User::factory()->superAdmin()->create())
        ->get(route('profile.edit'))
        ->assertOk()
        ->getContent();

    expect(preg_match('/<a\b[^>]*data-test="back-button"[^>]*>/', $html, $button))->toBe(1)
        ->and($button[0])->toContain('href="'.route('settings.index').'"');
});

test('top-level backoffice pages do not show a back button', function () {
    $this->actingAs(User::factory()->superAdmin()->create())
        ->get(route('settings.index'))
        ->assertOk()
        ->assertDontSee('data-test="back-button"', false);
});
