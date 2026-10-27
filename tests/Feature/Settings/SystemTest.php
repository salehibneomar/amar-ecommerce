<?php

use App\Enums\System\MaintenanceStatusEnum;
use App\Livewire\Backoffice\Settings\Branding;
use App\Livewire\Backoffice\Settings\System;
use App\Models\User;
use App\Services\Backoffice\System\SystemSettingService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('sensitive settings pages require password confirmation', function (string $route) {
    $user = User::factory()->superAdmin()->create();

    $this->actingAs($user)
        ->get(route($route))
        ->assertRedirect(route('password.confirm'));

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route($route))
        ->assertOk();
})->with(['system.edit', 'branding.edit', 'manage.analytics-trackers.index']);

test('customers cannot open the system settings page', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('system.edit'))
        ->assertRedirect(route('user.dashboard'));
});

test('administrators can clear and rebuild the application cache', function (string $action, string $command) {
    Process::fake();

    $this->actingAs(User::factory()->superAdmin()->create());

    Livewire::test(System::class)
        ->call($action)
        ->assertHasNoErrors()
        ->assertRedirect(route('system.edit'));

    Process::assertRan(fn ($process): bool => $process->command[2] === $command && $process->path === base_path());
})->with([
    'clear cache' => ['clearCache', 'optimize:clear'],
    'optimize' => ['optimize', 'optimize'],
]);

function confirmedAdmin(): User
{
    $admin = User::factory()->superAdmin()->create();
    test()->actingAs($admin)->withSession(['auth.password_confirmed_at' => time()]);

    return $admin;
}

test('staff without the system permission cannot open the system settings pages', function (string $route) {
    $this->actingAs(User::factory()->moderator()->create())
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route($route))
        ->assertRedirect(route('dashboard'));
})->with(['system.edit', 'branding.edit']);

test('enabling maintenance without times takes the site down until it is turned off', function () {
    confirmedAdmin();

    Livewire::test(System::class)
        ->set('maintenanceEnabled', true)
        ->call('saveMaintenance')
        ->assertHasNoErrors()
        ->assertSee(url(app(SystemSettingService::class)->maintenanceSecret()))
        ->assertSeeHtml('data-test="bypass-link"');

    expect(app(SystemSettingService::class)->maintenanceStatus())->toBe(MaintenanceStatusEnum::Active);

    auth()->logout();

    $this->get(route('home'))
        ->assertStatus(503)
        ->assertSee(__('Back soon'))
        ->assertDontSee(__('Go to home page'))
        ->assertHeaderMissing('Retry-After');

    $bypass = $this->get('/'.app(SystemSettingService::class)->maintenanceSecret())
        ->assertRedirect('/')
        ->getCookie('laravel_maintenance', false);

    $this->withUnencryptedCookie('laravel_maintenance', $bypass->getValue())
        ->get(route('home'))
        ->assertOk();

    app(SystemSettingService::class)->saveMaintenance(false);

    $this->get(route('home'))->assertOk();
    expect(app(SystemSettingService::class)->maintenanceSecret())->toBeNull();
});

test('admin pages stay open during maintenance while public and customer pages are blocked', function () {
    app(SystemSettingService::class)->saveMaintenance(true);

    $this->get(route('home'))->assertStatus(503);
    $this->get(route('user.login'))->assertStatus(503);
    $this->get(route('login'))->assertOk();

    $this->actingAs(User::factory()->create())
        ->get(route('user.dashboard'))
        ->assertStatus(503);

    confirmedAdmin();

    $this->get(route('dashboard'))->assertOk();
    $this->get(route('manage.faqs.index'))->assertOk();
    $this->get(route('settings.index'))->assertOk();
    $this->get(route('system.edit'))->assertOk();
});

test('a maintenance window starts and ends on its own', function () {
    $this->travelTo(now()->startOfMinute());
    $service = app(SystemSettingService::class);
    $service->saveMaintenance(true, now()->addHour(), now()->addHours(3));

    expect($service->maintenanceStatus())->toBe(MaintenanceStatusEnum::Scheduled);
    $this->get(route('home'))->assertOk();

    $this->travel(2)->hours();

    expect($service->maintenanceStatus())->toBe(MaintenanceStatusEnum::Active);
    $this->get(route('home'))
        ->assertStatus(503)
        ->assertHeader('Retry-After', '3600')
        ->assertSee(__('Expected back by :time', ['time' => formatDateTime(now()->addHour())]));

    $this->travel(2)->hours();

    expect($service->maintenanceStatus())->toBe(MaintenanceStatusEnum::Ended);
    $this->get(route('home'))->assertOk();
});

test('the maintenance end must be in the future and after the start', function () {
    confirmedAdmin();

    Livewire::test(System::class)
        ->set('maintenanceEnabled', true)
        ->set('maintenanceStartsAt', now()->addDays(2)->format('Y-m-d\TH:i'))
        ->set('maintenanceEndsAt', now()->addDay()->format('Y-m-d\TH:i'))
        ->call('saveMaintenance')
        ->assertHasErrors(['maintenanceEndsAt' => 'after']);

    expect(app(SystemSettingService::class)->isMaintenanceEnabled())->toBeFalse();
});

test('the down and up commands use the same maintenance setting', function () {
    $this->artisan('down')->assertSuccessful();

    expect(app(SystemSettingService::class)->maintenanceStatus())->toBe(MaintenanceStatusEnum::Active);
    $this->get(route('home'))->assertStatus(503);

    $this->artisan('up')->assertSuccessful();

    expect(app(SystemSettingService::class)->maintenanceStatus())->toBe(MaintenanceStatusEnum::Off);
    $this->get(route('home'))->assertOk();
});

test('an uploaded logo replaces the default and removing it falls back', function () {
    Storage::fake('public');
    confirmedAdmin();
    $default = asset('assets/logo/logo.png');

    Livewire::test(Branding::class)
        ->set('logoUpload', UploadedFile::fake()->image('brand.png', 400, 120))
        ->set('faviconUpload', UploadedFile::fake()->createWithContent('favicon.svg', '<svg xmlns="http://www.w3.org/2000/svg"></svg>'))
        ->call('saveBrand')
        ->assertHasErrors(['faviconUpload']);

    Livewire::test(Branding::class)
        ->set('logoUpload', UploadedFile::fake()->image('brand.png', 400, 120))
        ->call('saveBrand')
        ->assertHasNoErrors();

    $logo = app(SystemSettingService::class)->brandUrls()['logo'];
    expect($logo)->not->toBe($default);
    $this->get(route('about'))->assertSee($logo)->assertDontSee($default);

    Livewire::test(Branding::class)
        ->set('removeLogo', true)
        ->call('saveBrand')
        ->assertHasNoErrors();

    expect(app(SystemSettingService::class)->brandUrls()['logo'])->toBe($default);
    $this->get(route('about'))->assertSee($default);
});
