<?php

use App\Enums\Content\OfferTypeEnum;
use App\Enums\User\PermissionEnum;
use App\Livewire\Backoffice\Offers\Index;
use App\Models\Category;
use App\Models\Offer;
use App\Models\User;
use App\Services\Backoffice\Content\OfferService;
use App\Services\Frontend\Shop\PageService;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

test('offers need the permission and a confirmed password', function () {
    $staff = User::factory()->moderator()->create();

    $this->actingAs($staff)->get(route('manage.offers.index'))->assertRedirect(route('dashboard'));

    $staff->givePermissionTo(PermissionEnum::ManageOffers->value);

    $this->actingAs($staff->refresh())->get(route('manage.offers.index'))->assertRedirect(route('password.confirm'));
    $this->actingAs($staff)->withSession(['auth.password_confirmed_at' => time()])->get(route('manage.offers.index'))->assertOk();
});

test('a specific offer needs categories picked by search and saves them', function () {
    $science = Category::factory()->create(['name' => 'Science']);
    $history = Category::factory()->create(['name' => 'History']);
    Category::factory()->create(['name' => 'Poetry']);
    Category::factory()->create(['name' => 'Social Science', 'parent_id' => $history->id]);

    $component = Livewire::actingAs(User::factory()->superAdmin()->create())->test(Index::class)
        ->call('createOffer')
        ->set('name', 'Science Week')
        ->set('headline', 'Books on sale')
        ->set('discountPercentage', '15')
        ->set('type', OfferTypeEnum::Specific->value)
        ->call('saveOffer')
        ->assertHasErrors(['categoryIds' => 'required']);

    $component->set('categorySearch', 'sci')
        ->assertSee('Science')
        ->assertDontSee('Poetry')
        ->assertDontSee('Social Science')
        ->assertSee('History')
        ->call('addCategory', $science->id)
        ->assertSet('categorySearch', '')
        ->call('addCategory', $history->id)
        ->call('addCategory', $history->id)
        ->assertSet('categoryIds', [$science->id, $history->id])
        ->call('removeCategory', $history->id)
        ->call('addCategory', $history->id)
        ->call('saveOffer')
        ->assertHasNoErrors();

    $offer = Offer::query()->sole();

    expect($offer->type)->toBe(OfferTypeEnum::Specific)
        ->and($offer->categories->modelKeys())->toEqualCanonicalizing([$science->id, $history->id]);
});

test('searching a child category of any depth offers its root category', function () {
    $root = Category::factory()->create(['name' => 'Literature']);
    $child = Category::factory()->create(['name' => 'Novels', 'parent_id' => $root->id]);
    Category::factory()->create(['name' => 'Gothic Mysteries', 'parent_id' => $child->id]);
    Category::factory()->create(['name' => 'Cooking']);

    $component = Livewire::actingAs(User::factory()->superAdmin()->create())->test(Index::class)
        ->call('createOffer')
        ->set('type', OfferTypeEnum::Specific->value)
        ->set('categorySearch', 'gothic');

    expect($component->get('categoryResults'))->toBe([$root->id => 'Literature']);

    $component->call('addCategory', $root->id)->set('categorySearch', 'novels');

    expect($component->get('categoryResults'))->toBe([]);
});

test('an offer needs a headline and saves it', function () {
    $component = Livewire::actingAs(User::factory()->superAdmin()->create())->test(Index::class)
        ->call('createOffer')
        ->set('name', 'Eid')
        ->set('discountPercentage', '10')
        ->call('saveOffer')
        ->assertHasErrors(['headline' => 'required']);

    $component->set('headline', 'Books for Eid')
        ->call('saveOffer')
        ->assertHasNoErrors();

    expect(Offer::query()->sole()->headline)->toBe('Books for Eid');
});

test('global offers show no categories in their details', function () {
    $offer = Offer::factory()->create(['type' => OfferTypeEnum::Global]);

    Livewire::actingAs(User::factory()->superAdmin()->create())->test(Index::class)
        ->call('viewOffer', $offer->id)
        ->assertSet('details', fn (array $details): bool => ! array_key_exists(__('Categories'), $details));
});

test('switching an offer to global clears its categories', function () {
    $offer = Offer::factory()->create(['type' => OfferTypeEnum::Specific]);
    $offer->categories()->attach(Category::factory()->create());

    Livewire::actingAs(User::factory()->superAdmin()->create())->test(Index::class)
        ->call('editOffer', $offer->id)
        ->set('type', OfferTypeEnum::Global->value)
        ->call('saveOffer')
        ->assertHasNoErrors();

    expect($offer->refresh()->type)->toBe(OfferTypeEnum::Global)
        ->and($offer->categories)->toBeEmpty();
});

test('a global offer overlapping a live global offer is saved inactive with a warning', function () {
    Offer::factory()->create(['name' => 'Eid Sale', 'starts_at' => now()->subDay(), 'ends_at' => now()->addDays(10)]);

    Livewire::actingAs(User::factory()->superAdmin()->create())->test(Index::class)
        ->call('createOffer')
        ->assertSet('isActive', false)
        ->assertSee('Eid Sale')
        ->set('name', 'Winter Sale')
        ->set('headline', 'Books on sale')
        ->set('discountPercentage', '10')
        ->set('isActive', true)
        ->call('saveOffer')
        ->assertHasNoErrors();

    expect(Offer::firstWhere('name', 'Winter Sale')->is_active)->toBeFalse();
});

test('a global offer scheduled after the live one can be active', function () {
    $live = Offer::factory()->create(['starts_at' => now()->subDay(), 'ends_at' => now()->addDays(10)]);

    Livewire::actingAs(User::factory()->superAdmin()->create())->test(Index::class)
        ->call('createOffer')
        ->assertSet('isActive', false)
        ->set('startsAt', now()->addDays(11)->format('Y-m-d'))
        ->assertDontSee(__('Turn it off first'))
        ->set('isActive', true)
        ->set('name', 'Next Month')
        ->set('headline', 'Books on sale')
        ->set('discountPercentage', '10')
        ->call('saveOffer')
        ->assertHasNoErrors();

    expect(Offer::firstWhere('name', 'Next Month')->is_active)->toBeTrue();

    Livewire::actingAs(User::factory()->superAdmin()->create())->test(Index::class)
        ->call('editOffer', $live->id)
        ->assertSet('isActive', true);
});

test('expired and inactive global offers do not block a new active offer', function () {
    Offer::factory()->create(['starts_at' => now()->subDays(20), 'ends_at' => now()->subDays(5)]);
    Offer::factory()->inactive()->create();

    expect(app(OfferService::class)->findLiveGlobalOverlap(null, null, null))->toBeNull();
});

test('the service refuses a second active global offer in the same period', function () {
    Offer::factory()->create();

    app(OfferService::class)->create([
        'name' => 'Sneaky',
        'type' => OfferTypeEnum::Global->value,
        'discount_percentage' => 10,
        'is_active' => true,
    ]);
})->throws(ValidationException::class);

test('global only mode hides offer types and the shop ignores specific offers', function () {
    config(['shop.offer_global_only' => true]);
    $category = Category::factory()->create();
    $specific = Offer::factory()->create(['type' => OfferTypeEnum::Specific, 'discount_percentage' => 40]);
    $specific->categories()->attach($category);

    Livewire::actingAs(User::factory()->superAdmin()->create())->test(Index::class)
        ->assertSee(__('All offers are global'))
        ->assertDontSee(__('Specific'))
        ->call('editOffer', $specific->id)
        ->assertSet('type', OfferTypeEnum::Global->value)
        ->assertDontSeeHtml('wire:model.live="type"');

    expect(app(PageService::class)->bestOffersFor([$category->id])[$category->id])->toBeNull();
});

test('roles need a confirmed password and their permission cannot be granted', function () {
    expect(PermissionEnum::groupedOptions())->each->not->toHaveKey(PermissionEnum::ManageRoles->value);

    $this->actingAs(User::factory()->superAdmin()->create())->get(route('manage.roles.index'))->assertRedirect(route('password.confirm'));
});
