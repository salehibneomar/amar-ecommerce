<?php

use App\Enums\User\PermissionEnum;
use App\Models\User;

test('guests cannot access sales reports', function () {
    $this->get(route('manage.sales-reports.index'))
        ->assertRedirect(route('login'));
});

test('regular users are redirected from sales reports', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('manage.sales-reports.index'))
        ->assertRedirect(route('user.dashboard'));
});

test('staff need the view reports permission to open sales reports', function () {
    $staff = User::factory()->editor()->create();

    $this->actingAs($staff)
        ->get(route('manage.sales-reports.index'))
        ->assertRedirect(route('dashboard'));

    $staff->givePermissionTo(PermissionEnum::ViewReports->value);

    $this->actingAs($staff->fresh())
        ->get(route('manage.sales-reports.index'))
        ->assertOk()
        ->assertSeeLivewire('backoffice.sales-reports.index');
});
