<?php

use App\Enums\Payment\PaymentGatewayEnum;
use App\Enums\User\PermissionEnum;
use App\Livewire\Backoffice\Settings\Payments;
use App\Models\PaymentGateway;
use App\Models\User;
use App\Services\Payment\PaymentService;
use Database\Seeders\PaymentGatewaySeeder;
use Livewire\Livewire;

test('saving switches and order changes the gateways and their order at checkout', function () {
    config(['payment.stripe.key' => 'pk_test_key', 'payment.stripe.secret' => 'sk_test_secret']);
    PaymentGateway::factory()->create(['gateway' => PaymentGatewayEnum::Cod, 'sort_order' => 0]);
    PaymentGateway::factory()->disabled()->create(['gateway' => PaymentGatewayEnum::Stripe, 'sort_order' => 1]);

    expect(app(PaymentService::class)->gateways())->toBe([PaymentGatewayEnum::Cod]);

    $component = Livewire::actingAs(User::factory()->superAdmin()->create())->test(Payments::class)
        ->assertSet('gateways.0.gateway', PaymentGatewayEnum::Cod->value)
        ->assertSet('gateways.1.gateway', PaymentGatewayEnum::Stripe->value)
        ->call('move', 1, -1)
        ->set('gateways.0.is_enabled', true)
        ->call('saveGateways')
        ->assertHasNoErrors();

    expect($component->get('gateways.0.gateway'))->toBe(PaymentGatewayEnum::Stripe->value)
        ->and(app(PaymentService::class)->gateways())->toBe([PaymentGatewayEnum::Stripe, PaymentGatewayEnum::Cod]);
});

test('a gateway that is not configured cannot be turned on', function () {
    config(['payment.stripe.key' => null, 'payment.stripe.secret' => null]);

    $component = Livewire::actingAs(User::factory()->superAdmin()->create())->test(Payments::class);
    $index = array_search(PaymentGatewayEnum::Stripe->value, array_column($component->get('gateways'), 'gateway'), true);

    $component->set("gateways.{$index}.is_enabled", true)
        ->call('saveGateways')
        ->assertHasErrors(["gateways.{$index}.is_enabled"]);

    expect(PaymentGateway::query()->exists())->toBeFalse();
});

test('a gateway that is not installed cannot be moved', function () {
    config(['payment.stripe.key' => null, 'payment.stripe.secret' => null]);

    $component = Livewire::actingAs(User::factory()->superAdmin()->create())->test(Payments::class)
        ->assertSee(__('Not Installed'));
    $order = array_column($component->get('gateways'), 'gateway');
    $index = array_search(PaymentGatewayEnum::Stripe->value, $order, true);

    $component->call('move', $index, $index === 0 ? 1 : -1);

    expect(array_column($component->get('gateways'), 'gateway'))->toBe($order);
});

test('manual payments can be turned on without numbers', function () {
    $component = Livewire::actingAs(User::factory()->superAdmin()->create())->test(Payments::class);
    $index = array_search(PaymentGatewayEnum::Manual->value, array_column($component->get('gateways'), 'gateway'), true);

    $component->set("gateways.{$index}.is_enabled", true)->call('saveGateways')->assertHasNoErrors();

    expect(app(PaymentService::class)->gateways())->toBe([PaymentGatewayEnum::Manual]);
});

test('manual numbers are optional', function () {
    Livewire::actingAs(User::factory()->superAdmin()->create())->test(Payments::class)
        ->set('bkashNumber', '0171234567')
        ->call('saveManualNumbers')
        ->assertHasErrors(['bkashNumber' => 'digits'])
        ->set('bkashNumber', '')
        ->set('nagadNumber', '01812345678')
        ->call('saveManualNumbers')
        ->assertHasNoErrors();

    expect(PaymentGateway::query()->where('gateway', PaymentGatewayEnum::Manual)->value('settings'))->toBe(['bkash_number' => null, 'nagad_number' => '01812345678']);
});

test('staff without the permission cannot open the payments page', function () {
    $moderator = User::factory()->moderator()->create();

    $this->actingAs($moderator)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('payments.edit'))
        ->assertRedirect(route('dashboard'));

    $moderator->givePermissionTo(PermissionEnum::ManagePaymentGateways->value);

    $this->actingAs($moderator->refresh())
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('payments.edit'))
        ->assertOk();
});

test('the payments page requires password confirmation', function () {
    $this->actingAs(User::factory()->superAdmin()->create())
        ->get(route('payments.edit'))
        ->assertRedirect(route('password.confirm'));
});

test('the seeder turns on the gateways from the environment and copies the manual numbers', function () {
    config([
        'payment.enabled' => [PaymentGatewayEnum::Manual->value, PaymentGatewayEnum::Cod->value],
        'payment.manual.numbers.bkash' => '01712345678',
        'payment.manual.numbers.nagad' => null,
    ]);

    $this->seed(PaymentGatewaySeeder::class);

    expect(PaymentGateway::query()->count())->toBe(count(PaymentGatewayEnum::cases()))
        ->and(app(PaymentService::class)->gateways())->toBe([PaymentGatewayEnum::Manual, PaymentGatewayEnum::Cod])
        ->and(PaymentGateway::query()->where('gateway', PaymentGatewayEnum::Manual)->value('settings'))->toContain('01712345678');
});
