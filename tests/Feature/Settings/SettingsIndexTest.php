<?php

use App\Models\User;

test('super admins see every settings item', function () {
    $this->actingAs(User::factory()->superAdmin()->create())
        ->get(route('settings.index'))
        ->assertOk()
        ->assertSee(route('security.edit'))
        ->assertSee(route('manage.roles.index'))
        ->assertSee(route('system.edit'));
});

test('the profile page is in the backoffice user menu, not the settings list', function () {
    $html = $this->actingAs(User::factory()->superAdmin()->create())
        ->get(route('settings.index'))
        ->assertOk()
        ->assertDontSee(__('Update your name, email address and photo'))
        ->getContent();

    expect(preg_match('/<a\b[^>]*href="'.preg_quote(route('profile.edit'), '/').'"/', $html))->toBe(1);
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
