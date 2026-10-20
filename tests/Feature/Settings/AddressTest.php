<?php

use App\Livewire\Frontend\Cart\Checkout;
use App\Livewire\Frontend\Profile\Index as FrontendProfile;
use App\Models\User;
use Livewire\Livewire;

test('customers can save separate shipping and billing addresses from their profile', function () {
    $customer = User::factory()->create();

    Livewire::actingAs($customer)->test(FrontendProfile::class)
        ->assertSet('billingSameAsShipping', true)
        ->set('shippingAddress', 'House 1, Road 2, Dhanmondi, Dhaka')
        ->set('billingSameAsShipping', false)
        ->set('billingAddress', 'House 9, Road 8, Gulshan, Dhaka')
        ->call('updateAddresses')
        ->assertHasNoErrors();

    expect($customer->refresh())
        ->shipping_address->toBe('House 1, Road 2, Dhanmondi, Dhaka')
        ->billing_address->toBe('House 9, Road 8, Gulshan, Dhaka');

    Livewire::actingAs($customer)->test(FrontendProfile::class)
        ->assertSet('billingSameAsShipping', false)
        ->assertSet('billingAddress', 'House 9, Road 8, Gulshan, Dhaka');
});

test('staff billing address follows the shipping address when marked the same', function () {
    $staff = User::factory()->editor()->create(['billing_address' => 'Old billing address']);

    Livewire::actingAs($staff)->test('pages::settings.profile')
        ->set('shippingAddress', 'Office 3, Banani, Dhaka')
        ->set('billingSameAsShipping', true)
        ->call('updateAddresses')
        ->assertHasNoErrors();

    expect($staff->refresh())
        ->shipping_address->toBe('Office 3, Banani, Dhaka')
        ->billing_address->toBe('Office 3, Banani, Dhaka');
});

test('addresses longer than the limit are rejected', function () {
    $customer = User::factory()->create();

    Livewire::actingAs($customer)->test(FrontendProfile::class)
        ->set('shippingAddress', str_repeat('a', 501))
        ->call('updateAddresses')
        ->assertHasErrors('shippingAddress');

    expect($customer->refresh()->shipping_address)->toBeNull();
});

test('guests cannot open the customer profile', function () {
    $this->get(route('user.profile'))->assertRedirect(route('user.login'));
});

test('checkout prefills the saved shipping address', function () {
    $customer = User::factory()->create(['shipping_address' => 'House 1, Dhaka']);

    Livewire::actingAs($customer)->test(Checkout::class)
        ->assertSet('shippingAddress', 'House 1, Dhaka');
});
