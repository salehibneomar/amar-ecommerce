<?php

use App\Livewire\Backoffice\Users\Index;
use App\Models\User;
use Livewire\Livewire;

test('guests cannot access the management pages', function () {
    $this->get(route('manage.users.index'))
        ->assertRedirect(route('login'));
});

test('regular users are redirected to the dashboard from management pages', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('manage.users.index'))
        ->assertRedirect(route('user.dashboard'));
});

test('administrators can access the dashboard and user management pages', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin);

    $this->get(route('dashboard'))->assertOk();
    $this->get(route('manage.users.index'))->assertOk();
});

test('administrators can search users and block and unblock them', function () {
    $admin = User::factory()->superAdmin()->create();
    $target = User::factory()->create(['name' => 'Searchable User']);

    $this->actingAs($admin)
        ->get(route('manage.users.index', ['search' => 'Searchable']))
        ->assertOk()
        ->assertSee('Searchable User');

    $this->post(route('manage.users.block', $target))
        ->assertRedirect()
        ->assertSessionHas('success', 'User blocked successfully.');

    expect($target->refresh()->isBlocked())->toBeTrue();

    $this->post(route('manage.users.unblock', $target))
        ->assertRedirect()
        ->assertSessionHas('success', 'User unblocked successfully.');

    expect($target->refresh()->isBlocked())->toBeFalse();
});

test('administrators are redirected to the dashboard when trying to block themselves', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin)
        ->post(route('manage.users.block', $admin))
        ->assertRedirect(route('dashboard'));

    expect($admin->refresh()->isBlocked())->toBeFalse();
});

test('administrators can filter users by status and change the page size', function () {
    $admin = User::factory()->superAdmin()->create();
    User::factory()->create(['name' => 'Active User']);
    User::factory()->blocked()->create(['name' => 'Blocked User']);

    $this->actingAs($admin);

    Livewire::test(Index::class)
        ->set('status', 'blocked')
        ->assertSet('status', 'blocked')
        ->assertSee('Blocked User')
        ->assertDontSee('Active User')
        ->set('perPage', 5)
        ->assertSet('perPage', 5);
});

test('administrators receive the default page size for an invalid page size', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin);

    Livewire::test(Index::class)
        ->set('perPage', 15)
        ->assertSet('perPage', 10);
});

test('administrators see sortable user table headers except actions', function () {
    $admin = User::factory()->superAdmin()->create();

    User::factory()->create(['name' => 'First User']);
    User::factory()->create(['name' => 'Second User']);
    $this->actingAs($admin);

    Livewire::test(Index::class)
        ->assertSet('sortBy', 'id')
        ->assertSet('sortDirection', 'desc')
        ->assertSeeInOrder(['Second User', 'First User'])
        ->assertSeeHtml("sort('id')")
        ->assertSeeHtml("sort('name')")
        ->assertSeeHtml("sort('email')")
        ->assertSeeHtml("sort('status')")
        ->assertSeeHtml('font-bold text-zinc-950 dark:text-white')
        ->assertDontSeeHtml("sort('actions')");
});

test('administrators can sort users and toggle the sort direction', function () {
    $admin = User::factory()->superAdmin()->create();
    User::factory()->create(['name' => 'Beta User']);
    User::factory()->create(['name' => 'Alpha User']);

    $this->actingAs($admin);

    Livewire::test(Index::class)
        ->call('sort', 'name')
        ->assertSet('sortBy', 'name')
        ->assertSet('sortDirection', 'asc')
        ->assertSeeInOrder(['Alpha User', 'Beta User'])
        ->call('sort', 'name')
        ->assertSet('sortDirection', 'desc')
        ->assertSeeInOrder(['Beta User', 'Alpha User']);
});

test('administrators view a customer\'s details from the row actions', function () {
    $customer = User::factory()->create(['name' => 'Detail Customer', 'phone' => '01700000000']);

    Livewire::actingAs(User::factory()->superAdmin()->create())->test(Index::class)
        ->assertSeeHtml('wire:click="viewUser('.$customer->id.')"')
        ->call('viewUser', $customer->id)
        ->assertSet('showDetails', true)
        ->assertSee(['Detail Customer', '01700000000']);
});

test('empty fields in the details view show the configured empty value', function () {
    config(['app.empty_value' => 'Not set']);
    $customer = User::factory()->create(['phone' => null, 'address' => null]);

    Livewire::actingAs(User::factory()->superAdmin()->create())->test(Index::class)
        ->call('viewUser', $customer->id)
        ->assertSee('Not set');
});
