<?php

use App\Enums\System\ThemeColorEnum;
use App\Livewire\Backoffice\Settings\Appearance;
use App\Models\User;
use App\Services\Backoffice\System\SystemSettingService;
use Illuminate\Mail\Markdown;
use Livewire\Livewire;

test('the appearance page shows the brand color picker to administrators only', function () {
    $this->actingAs(User::factory()->superAdmin()->create())
        ->get(route('appearance.edit'))
        ->assertOk()
        ->assertSee('data-test="save-theme-button"', false);

    $this->actingAs(User::factory()->moderator()->create())
        ->get(route('appearance.edit'))
        ->assertOk()
        ->assertDontSee('data-test="save-theme-button"', false);
});

test('staff without the theme permission cannot save a theme', function () {
    $this->actingAs(User::factory()->moderator()->create());

    Livewire::test(Appearance::class)
        ->set('themeColor', ThemeColorEnum::Teal->value)
        ->call('saveTheme');

    expect(app(SystemSettingService::class)->themeColor())->toBe(ThemeColorEnum::Default);
});

test('administrators can change the theme for every page and email', function () {
    $this->actingAs(User::factory()->superAdmin()->create());

    Livewire::test(Appearance::class)
        ->assertSet('themeColor', ThemeColorEnum::Default->value)
        ->set('themeColor', ThemeColorEnum::Teal->value)
        ->call('saveTheme')
        ->assertHasNoErrors()
        ->assertRedirect(route('appearance.edit'));

    $teal = themePresets()['teal']['palette'];

    expect(app(SystemSettingService::class)->themeColor())->toBe(ThemeColorEnum::Teal);

    $this->get(route('home'))->assertSee("--theme-brand: {$teal[600]}", false);

    expect(app(Markdown::class)->theme('brand')->render('mail::message', ['slot' => 'Hello'])->toHtml())->toContain($teal[50]);
});

test('an unknown theme is rejected', function () {
    $this->actingAs(User::factory()->superAdmin()->create());

    Livewire::test(Appearance::class)
        ->set('themeColor', 'neon')
        ->call('saveTheme')
        ->assertHasErrors(['themeColor']);

    expect(app(SystemSettingService::class)->themeColor())->toBe(ThemeColorEnum::Default);
});

test('every theme has a full palette with readable brand colors', function (ThemeColorEnum $color) {
    $preset = themePresets()[$color->value];
    $theme = app(SystemSettingService::class)->theme($color);

    expect(array_keys($preset['palette']))->toBe([50, 100, 200, 300, 400, 500, 600, 700, 800, 900, 950])
        ->and(colorContrastRatio($theme['brand'], $theme['foreground']))->toBeGreaterThanOrEqual(4.5)
        ->and(colorContrastRatio($theme['brand_hover'], $theme['foreground']))->toBeGreaterThanOrEqual(4.5)
        ->and(colorContrastRatio($theme['brand_dark'], $theme['foreground']))->toBeGreaterThanOrEqual(3);
})->with(ThemeColorEnum::cases());
