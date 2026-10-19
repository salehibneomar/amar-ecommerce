<?php

use App\Livewire\Frontend\Search\Index;
use App\Models\Book;
use Livewire\Livewire;

test('the navbar search shows the top five in-stock books whose title matches', function () {
    Book::factory()->count(6)->sequence(fn ($sequence) => ['name' => 'Rice Guide '.($sequence->index + 1)])->create();
    Book::factory()->create(['name' => 'Mango Basics']);
    Book::factory()->archived()->create(['name' => 'Rice Archive']);

    Livewire::test(Index::class)
        ->set('query', 'rice')
        ->assertCount('visibleResults', 5)
        ->assertSee('Rice Guide')
        ->assertDontSee(['Mango Basics', 'Rice Archive'])
        ->assertSeeHtml('data-test="search-see-more"')
        ->assertSeeHtml('href="'.route('shop', ['q' => 'rice']).'"');
});

test('the navbar search hides see more when every match fits', function () {
    Book::factory()->count(5)->sequence(fn ($sequence) => ['name' => 'Rice Guide '.($sequence->index + 1)])->create();

    Livewire::test(Index::class)
        ->set('query', 'rice')
        ->assertCount('visibleResults', 5)
        ->assertDontSeeHtml('data-test="search-see-more"');
});

test('the navbar search waits for two characters and reports no matches', function () {
    Book::factory()->create(['name' => 'Rice Guide']);

    Livewire::test(Index::class)
        ->set('query', 'r')
        ->assertCount('visibleResults', 0)
        ->assertDontSeeHtml('data-test="search-results"')
        ->set('query', 'zz')
        ->assertSee('No books match');
});

test('every search box uses the global debounce and clears without it', function () {
    config(['app.search_debounce_ms' => 250]);

    Livewire::test(Index::class)
        ->assertSeeHtml('wire:model="query"')
        ->assertSeeHtml('timer = setTimeout(() =&gt; $wire.$commit(), 250)')
        ->assertSeeHtml('x-on:clear="clearTimeout(timer); $wire.$commit()"');
    $this->get(route('shop'))->assertSee('timer = setTimeout(() =&gt; $wire.$commit(), 250)', false);
});
