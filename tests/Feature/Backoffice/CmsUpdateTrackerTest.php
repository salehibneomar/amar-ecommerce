<?php

use App\Livewire\Backoffice\Faqs\Index as FaqIndex;
use App\Livewire\Backoffice\Offers\Index as OfferIndex;
use App\Models\Faq;
use App\Models\Offer;
use App\Models\Product;
use App\Models\User;
use Livewire\Livewire;

test('staff edits record the last updater and show it in the details modal', function () {
    $admin = User::factory()->superAdmin()->create();
    $faq = Faq::factory()->create();

    Livewire::actingAs($admin)->test(FaqIndex::class)
        ->call('editFaq', $faq->id)
        ->set('title', 'Updated title')
        ->call('saveFaq')
        ->assertHasNoErrors();

    expect($faq->refresh()->updated_by)->toBe($admin->id);

    Livewire::actingAs($admin)->test(FaqIndex::class)
        ->call('viewFaq', $faq->id)
        ->assertSet('showDetails', true)
        ->assertSee($faq->lastUpdateLabel());

    Livewire::actingAs($admin)->test(OfferIndex::class)
        ->call('viewOffer', Offer::factory()->create(['is_active' => true])->id)
        ->assertSee(__('Yes'));
});

test('customer actions do not change the last updater', function () {
    $product = Product::factory()->create();

    $this->actingAs(User::factory()->create());
    $product->update(['name' => 'Customer touched']);

    expect($product->refresh()->updated_by)->toBeNull();
});
