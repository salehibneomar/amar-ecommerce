<?php

use App\Enums\User\PermissionEnum;
use App\Enums\User\StaffCompensationTypeEnum;
use App\Enums\User\UserRoleEnum;
use App\Enums\User\UserStatusEnum;
use App\Livewire\Backoffice\Staff\Index;
use App\Livewire\Backoffice\StaffPayments\Index as PaymentsIndex;
use App\Models\Order;
use App\Models\Role;
use App\Models\StaffPayment;
use App\Models\User;
use Livewire\Livewire;

test('only staff managers can manage staff and their payments', function () {
    $moderator = User::factory()->moderator()->create();

    $this->actingAs($moderator)->get(route('manage.staff.index'))->assertRedirect(route('dashboard'));
    $this->actingAs($moderator)->get(route('manage.staff.payments', $moderator->id))->assertRedirect(route('dashboard'));
    $this->actingAs(User::factory()->superAdmin()->create())->get(route('manage.staff.index'))->assertOk();
});

test('administrators add a moderator with one compensation type, then assign permissions separately', function () {
    $component = Livewire::actingAs(User::factory()->superAdmin()->create())->test(Index::class)
        ->call('createStaff')
        ->set('name', 'Rahim')
        ->set('email', 'rahim@example.com')
        ->set('password', 'secret123')
        ->set('compensationType', StaffCompensationTypeEnum::Percentage->value)
        ->set('compensationValue', '7')
        ->call('saveStaff')
        ->assertHasNoErrors();

    $moderator = User::query()->where('email', 'rahim@example.com')->firstOrFail();

    expect($moderator->permissions)->toBeEmpty();

    $component->call('editPermissions', $moderator->id)
        ->assertSet('showPermissions', true)
        ->set('permissions', [PermissionEnum::ManageOrders->value])
        ->call('savePermissions')
        ->assertHasNoErrors();

    $moderator->refresh()->unsetRelation('permissions');

    expect($moderator->hasRole(UserRoleEnum::Moderator->value))->toBeTrue()
        ->and($moderator->hasVerifiedEmail())->toBeTrue()
        ->and($moderator->can(PermissionEnum::ManageOrders->value))->toBeTrue()
        ->and($moderator->can(PermissionEnum::ViewReports->value))->toBeFalse()
        ->and($moderator->staffProfile->compensation_type)->toBe(StaffCompensationTypeEnum::Percentage)
        ->and($moderator->staffProfile->compensation_value)->toBe(7);
});

test('a percentage above 100 is rejected', function () {
    Livewire::actingAs(User::factory()->superAdmin()->create())->test(Index::class)
        ->set('name', 'Karim')
        ->set('email', 'karim@example.com')
        ->set('password', 'secret123')
        ->set('compensationType', StaffCompensationTypeEnum::Percentage->value)
        ->set('compensationValue', '120')
        ->call('saveStaff')
        ->assertHasErrors(['compensationValue' => 'max']);
});

test('moderators sign in on the admin login page', function () {
    User::factory()->moderator()->create(['email' => 'mod@example.com']);

    $this->post(route('login.store'), ['email' => 'mod@example.com', 'password' => '123456'])
        ->assertRedirect(route('dashboard'));

    $this->assertAuthenticated();
});

test('a percentage moderator is paid a share of the month\'s paid sales only', function () {
    $this->travelTo('2026-10-15 12:00:00');
    $admin = User::factory()->superAdmin()->create();
    $moderator = User::factory()->moderator()->create();
    $moderator->staffProfile()->create(['compensation_type' => StaffCompensationTypeEnum::Percentage, 'compensation_value' => 10]);
    Order::factory()->paid()->create(['total' => 1000, 'created_at' => '2026-09-03 10:00:00']);
    Order::factory()->paid()->create(['total' => 500, 'created_at' => '2026-09-28 10:00:00']);
    Order::factory()->create(['total' => 9000, 'created_at' => '2026-09-10 10:00:00']);
    Order::factory()->paid()->create(['total' => 7000, 'created_at' => '2026-10-01 10:00:00']);

    $component = Livewire::actingAs($admin)->test(PaymentsIndex::class, ['staffId' => $moderator->id, 'staffName' => $moderator->name])
        ->call('createPayment')
        ->assertSet('showForm', true)
        ->assertSet('periodMonth', 9)
        ->assertSet('amount', '150')
        ->call('savePayment')
        ->assertHasNoErrors();

    $payment = StaffPayment::sole();

    expect($payment->amount)->toBe(150)
        ->and($payment->total_sales_snapshot)->toBe(1500)
        ->and($payment->compensation_type_snapshot)->toBe(StaffCompensationTypeEnum::Percentage)
        ->and($payment->paid_by)->toBe($admin->id);

    $component->call('createPayment')
        ->call('savePayment')
        ->assertHasErrors('periodMonth');
});

test('a salaried moderator is suggested their fixed salary', function () {
    $moderator = User::factory()->moderator()->create();
    $moderator->staffProfile()->create(['compensation_type' => StaffCompensationTypeEnum::Salary, 'compensation_value' => 15000]);
    Order::factory()->paid()->create(['total' => 99999, 'created_at' => now()->subMonth()]);

    Livewire::actingAs(User::factory()->superAdmin()->create())->test(PaymentsIndex::class, ['staffId' => $moderator->id, 'staffName' => $moderator->name])
        ->assertSet('amount', '15000');
});

test('a payment can only be deleted from its own moderator', function () {
    $moderator = User::factory()->moderator()->create();
    $other = User::factory()->moderator()->create();
    $payment = StaffPayment::create([
        'user_id' => $other->id,
        'amount' => 100,
        'period_month' => 1,
        'period_year' => 2026,
        'compensation_type_snapshot' => StaffCompensationTypeEnum::Salary,
        'compensation_value_snapshot' => 100,
    ]);

    Livewire::actingAs(User::factory()->superAdmin()->create())->test(PaymentsIndex::class, ['staffId' => $moderator->id, 'staffName' => $moderator->name])
        ->call('confirmDeletePayment', $payment->id)
        ->call('deletePayment')
        ->assertNotFound();

    expect($payment->fresh())->not->toBeNull();
});

test('the payments page opens from the staff panel with a breadcrumb', function () {
    $moderator = User::factory()->moderator()->create(['name' => 'Payment Person']);

    $this->actingAs(User::factory()->superAdmin()->create())
        ->get(route('manage.staff.payments', $moderator->id))
        ->assertOk()
        ->assertSee(['Staff', 'Payment Person', 'Payments']);

    $this->actingAs(User::factory()->superAdmin()->create())
        ->get(route('manage.staff.payments', User::factory()->create()->id))
        ->assertNotFound();
});

test('administrators deactivate and reactivate moderators instead of deleting them', function () {
    $moderator = User::factory()->moderator()->create();

    $component = Livewire::actingAs(User::factory()->superAdmin()->create())->test(Index::class)
        ->call('confirmToggleStatus', $moderator->id)
        ->call('toggleStatus');

    expect($moderator->refresh()->status)->toBe(UserStatusEnum::Blocked)
        ->and($moderator->trashed())->toBeFalse();

    $component->call('confirmToggleStatus', $moderator->id)->call('toggleStatus');

    expect($moderator->refresh()->status)->toBe(UserStatusEnum::Active);
});

test('new staff get the default permissions of their role', function () {
    Role::findByName(UserRoleEnum::Editor->value)->syncPermissions([PermissionEnum::ManageFaqs->value]);

    Livewire::actingAs(User::factory()->superAdmin()->create())->test(Index::class)
        ->call('createStaff')
        ->set('name', 'Elena')
        ->set('email', 'elena@example.com')
        ->set('password', 'secret123')
        ->set('role', UserRoleEnum::Editor->value)
        ->set('compensationValue', '1000')
        ->call('saveStaff')
        ->assertHasNoErrors();

    $editor = User::query()->where('email', 'elena@example.com')->firstOrFail();

    expect($editor->isBackofficeUser())->toBeTrue()
        ->and($editor->can(PermissionEnum::ManageFaqs->value))->toBeTrue()
        ->and($editor->can(PermissionEnum::ManageOrders->value))->toBeFalse();

    $this->actingAs($editor)->get(route('manage.faqs.index'))->assertOk();
    $this->actingAs($editor)->get(route('manage.orders.index'))->assertRedirect(route('dashboard'));
});

test('only the super admin can assign the admin role', function () {
    $manager = User::factory()->moderator()->create();
    $manager->givePermissionTo(PermissionEnum::ManageStaff->value);

    Livewire::actingAs($manager)->test(Index::class)
        ->call('createStaff')
        ->set('name', 'Sneaky')
        ->set('email', 'sneaky@example.com')
        ->set('password', 'secret123')
        ->set('role', UserRoleEnum::Admin->value)
        ->set('compensationValue', '0')
        ->call('saveStaff')
        ->assertHasErrors('role');

    expect(User::query()->where('email', 'sneaky@example.com')->exists())->toBeFalse();
});

test('admins do not see themselves in the staff list and cannot deactivate themselves', function () {
    $admin = User::factory()->superAdmin()->create(['name' => 'Self Admin']);
    User::factory()->moderator()->create(['name' => 'Other Moderator']);

    Livewire::actingAs($admin)->test(Index::class)
        ->assertSee('Other Moderator')
        ->assertDontSee('Self Admin')
        ->call('confirmToggleStatus', $admin->id)
        ->call('toggleStatus')
        ->assertHasErrors('statusStaffId');

    expect($admin->refresh()->status)->toBe(UserStatusEnum::Active);
});

test('the super admin creates admins with every permission and can narrow one admin', function () {
    $superAdmin = User::factory()->superAdmin()->create();

    $component = Livewire::actingAs($superAdmin)->test(Index::class)
        ->call('createStaff')
        ->set('name', 'Ayesha')
        ->set('email', 'ayesha@example.com')
        ->set('password', 'secret123')
        ->set('role', UserRoleEnum::Admin->value)
        ->assertSee(__('Admins can do anything by default'))
        ->set('compensationValue', '0')
        ->call('saveStaff')
        ->assertHasNoErrors();

    $admin = User::query()->where('email', 'ayesha@example.com')->firstOrFail();

    expect($admin->can(PermissionEnum::ManageRoles->value))->toBeTrue()
        ->and($admin->isSuperAdmin())->toBeFalse();

    $component->call('editPermissions', $admin->id)
        ->assertSet('permissionsForAdmin', true)
        ->set('permissions', [PermissionEnum::ManageOrders->value])
        ->call('savePermissions');

    $admin->refresh()->unsetRelation('permissions');

    expect($admin->can(PermissionEnum::ManageOrders->value))->toBeTrue()
        ->and($admin->can(PermissionEnum::ManageRoles->value))->toBeFalse();
});

test('admins cannot create or change other admins and the super admin stays hidden', function () {
    $admin = User::factory()->admin()->create();
    $other = User::factory()->admin()->create();
    User::factory()->superAdmin()->create(['name' => 'The Owner']);

    Livewire::actingAs($admin)->test(Index::class)
        ->assertDontSee('The Owner')
        ->assertSet('roleOptions', fn (array $options): bool => ! array_key_exists(UserRoleEnum::Admin->value, $options))
        ->call('confirmToggleStatus', $other->id)
        ->call('toggleStatus')
        ->assertHasErrors('role');

    expect($other->refresh()->status)->toBe(UserStatusEnum::Active);
});

test('staff joining and leaving dates are saved and validated', function () {
    $component = Livewire::actingAs(User::factory()->superAdmin()->create())->test(Index::class)
        ->call('createStaff')
        ->assertSet('joinedAt', now()->format('Y-m-d'))
        ->set('name', 'Dated')
        ->set('email', 'dated@example.com')
        ->set('password', 'secret123')
        ->set('compensationValue', '100')
        ->set('joinedAt', '2026-03-01')
        ->set('leftAt', '2026-02-01')
        ->call('saveStaff')
        ->assertHasErrors(['leftAt' => 'after_or_equal']);

    $component->set('leftAt', '2026-09-30')->call('saveStaff')->assertHasNoErrors();

    $profile = User::query()->where('email', 'dated@example.com')->firstOrFail()->staffProfile;

    expect($profile->joined_at->format('Y-m-d'))->toBe('2026-03-01')
        ->and($profile->left_at->format('Y-m-d'))->toBe('2026-09-30');
});
