<?php

use App\Livewire\Frontend\Search\Index;
use App\Models\Product;
use Livewire\Livewire;

test('the navbar search shows the top five in-stock products whose name matches', function () {
    Product::factory()->count(6)->sequence(fn ($sequence) => ['name' => 'Rice Cooker '.($sequence->index + 1)])->create();
    Product::factory()->create(['name' => 'Mango Juice']);
    Product::factory()->archived()->create(['name' => 'Rice Cooker Archive']);

    Livewire::test(Index::class)
        ->set('query', 'rice')
        ->assertCount('visibleResults', 5)
        ->assertSee('Rice Cooker')
        ->assertDontSee(['Mango Juice', 'Rice Cooker Archive'])
        ->assertSeeHtml('data-test="search-see-more"')
        ->assertSeeHtml('href="'.route('shop', ['q' => 'rice']).'"');
});

test('the navbar search hides see more when every match fits', function () {
    Product::factory()->count(5)->sequence(fn ($sequence) => ['name' => 'Rice Cooker '.($sequence->index + 1)])->create();

    Livewire::test(Index::class)
        ->set('query', 'rice')
        ->assertCount('visibleResults', 5)
        ->assertDontSeeHtml('data-test="search-see-more"');
});

test('the navbar search waits for two characters and reports no matches', function () {
    Product::factory()->create(['name' => 'Rice Cooker']);

    Livewire::test(Index::class)
        ->set('query', 'r')
        ->assertCount('visibleResults', 0)
        ->assertDontSeeHtml('data-test="search-results"')
        ->set('query', 'zz')
        ->assertSee('No products match');
});

test('every search box uses the global debounce and clears without it', function () {
    config(['app.search_debounce_ms' => 250]);

    Livewire::test(Index::class)
        ->assertSeeHtml('wire:model="query"')
        ->assertSeeHtml('timer = setTimeout(() =&gt; $wire.$commit(), 250)')
        ->assertSeeHtml('x-on:clear="clearTimeout(timer); $wire.$commit()"');
    $this->get(route('shop'))->assertSee('timer = setTimeout(() =&gt; $wire.$commit(), 250)', false);
});
