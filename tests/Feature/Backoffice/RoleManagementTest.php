<?php

use App\Enums\User\PermissionEnum;
use App\Enums\User\UserRoleEnum;
use App\Livewire\Backoffice\Roles\Index;
use App\Models\Role;
use App\Models\User;
use Livewire\Livewire;

test('only users with the roles permission can open role management', function () {
    $this->actingAs(User::factory()->moderator()->create())->get(route('manage.roles.index'))->assertRedirect(route('dashboard'));
    $this->actingAs(User::factory()->superAdmin()->create())->withSession(['auth.password_confirmed_at' => time()])->get(route('manage.roles.index'))->assertOk()->assertSee(__('Moderator'));
});

test('admins create a role with permissions that its staff inherit', function () {
    Livewire::actingAs(User::factory()->superAdmin()->create())->test(Index::class)
        ->call('createRole')
        ->set('name', 'Writer')
        ->set('permissions', [PermissionEnum::ManageFaqs->value, PermissionEnum::ManageBanners->value])
        ->call('saveRole')
        ->assertHasNoErrors();

    $role = Role::query()->where('label', 'Writer')->sole();
    $writer = User::factory()->create();
    $writer->syncRoles($role);

    expect($role->name)->toBe('writer');

    expect($writer->isBackofficeUser())->toBeTrue()
        ->and($writer->can(PermissionEnum::ManageBanners->value))->toBeTrue()
        ->and($writer->can(PermissionEnum::ManageOrders->value))->toBeFalse();
});

test('role names are unique and the admin roles cannot be edited', function () {
    $moderator = Role::findByName(UserRoleEnum::Moderator->value);
    $admin = Role::findByName(UserRoleEnum::Admin->value);

    Livewire::actingAs(User::factory()->superAdmin()->create())->test(Index::class)
        ->call('createRole')
        ->set('name', 'moderator')
        ->call('saveRole')
        ->assertHasErrors(['name' => 'unique']);

    Livewire::actingAs(User::factory()->superAdmin()->create())->test(Index::class)
        ->call('editRole', $moderator->id)
        ->set('name', 'Order Desk')
        ->set('permissions', [PermissionEnum::ManageOrders->value])
        ->call('saveRole')
        ->assertHasNoErrors()
        ->call('editRole', $admin->id)
        ->assertHasErrors('name');

    expect($moderator->refresh()->name)->toBe('moderator')
        ->and($moderator->label)->toBe('Order Desk')
        ->and($moderator->permissions->pluck('name')->all())->toBe([PermissionEnum::ManageOrders->value])
        ->and($admin->refresh()->label)->toBe(__('Admin'))
        ->and(PermissionEnum::groupedOptions())->each->not->toHaveKey(PermissionEnum::ManageRoles->value);
});

test('built-in roles and roles with staff cannot be deleted', function () {
    $custom = Role::create(['name' => 'Writer']);
    User::factory()->create()->syncRoles('Writer');

    $component = Livewire::actingAs(User::factory()->superAdmin()->create())->test(Index::class)
        ->call('confirmDeleteRole', Role::findByName(UserRoleEnum::Editor->value)->id)
        ->call('deleteRole')
        ->assertHasErrors('role')
        ->call('confirmDeleteRole', $custom->id)
        ->call('deleteRole')
        ->assertHasErrors('role');

    expect(Role::query()->whereKey($custom->id)->exists())->toBeTrue();

    $custom->users()->detach();
    $component->call('confirmDeleteRole', $custom->id)->call('deleteRole')->assertHasNoErrors();

    expect(Role::query()->whereKey($custom->id)->exists())->toBeFalse();
});
