<?php

use App\Enums\Catalog\ProductStatusEnum;
use App\Livewire\Frontend\AiAssistant\Index;
use App\Models\AiAssistantEmbedding;
use App\Models\Book;
use App\Models\Faq;
use App\Models\User;
use App\Services\Frontend\AiAssistant\AiAssistantSearchService;
use App\Traits\AiAssistant\AiAssistantEmbeddingTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

beforeEach(function () {
    config([
        'ai-assistant.enabled' => true,
        'ai-assistant.provider' => 'gemini',
        'ai-assistant.providers.gemini.api_key' => 'test-key',
        'ai-assistant.providers.gemini.model' => 'test-model',
        'ai-assistant.providers.gemini.embedding_model' => null,
        'throttle.assistant' => 10,
    ]);
});

/**
 * @param  array<string, mixed>  $data
 */
function fakeGeminiJson(array $data): void
{
    Http::fake(['generativelanguage.googleapis.com/*' => Http::response([
        'candidates' => [['content' => ['parts' => [['text' => json_encode($data)]]]]],
    ])]);
}

test('a guest gets a reply from the assistant with the chat history', function () {
    fakeGeminiJson(['reply' => 'Try a Humayun Ahmed novel.', 'suggestions' => []]);

    Livewire::test(Index::class)
        ->set('messages', [
            ['role' => 'user', 'text' => 'Hi', 'suggestions' => []],
            ['role' => 'assistant', 'text' => 'Hello!', 'suggestions' => []],
        ])
        ->set('message', 'Suggest a novel')
        ->call('send')
        ->assertHasNoErrors()
        ->assertSet('message', '')
        ->assertSet('messages.2', ['role' => 'user', 'text' => 'Suggest a novel', 'suggestions' => []])
        ->assertSet('messages.3', ['role' => 'assistant', 'text' => 'Try a Humayun Ahmed novel.', 'suggestions' => []]);

    Http::assertSent(fn (Request $request): bool => str_contains($request->url(), 'test-model:generateContent')
        && $request->hasHeader('x-goog-api-key', 'test-key')
        && $request->data()['contents'][1]['role'] === 'model'
        && $request->data()['contents'][2]['parts'][0]['text'] === 'Suggest a novel'
        && $request->data()['generationConfig']['responseSchema']['type'] === 'OBJECT'
        && $request->data()['generationConfig']['maxOutputTokens'] === aiAssistantMaxOutputTokens()
        && ! isset($request->data()['generationConfig']['thinkingConfig']));
});

test('suggestions come only from listed candidates and show database prices', function () {
    $novel = Book::factory()->create(['name' => 'Padma Nadir Majhi', 'price' => 400, 'discount_percentage' => 25]);
    $archived = Book::factory()->archived()->create(['name' => 'Archived novel']);

    fakeGeminiJson(['reply' => 'You may like these.', 'suggestions' => [
        ['ref' => 1, 'reason' => 'A classic river novel.'],
        ['ref' => 2, 'reason' => 'Not a candidate.'],
        ['ref' => 99, 'reason' => 'Invented.'],
    ]]);

    Livewire::test(Index::class)
        ->set('message', 'A Bangla novel about rivers')
        ->call('send')
        ->assertSet('messages.1.suggestions', [['id' => $novel->id, 'reason' => 'A classic river novel.']])
        ->assertSee('Padma Nadir Majhi')
        ->assertSee(formatMoney(300))
        ->assertDontSee('Archived novel');

    Http::assertSent(fn (Request $request): bool => str_contains($request->data()['system_instruction']['parts'][0]['text'], '"ref":1,"name":"Padma Nadir Majhi"')
        && ! str_contains($request->data()['system_instruction']['parts'][0]['text'], '"id"')
        && ! str_contains($request->data()['system_instruction']['parts'][0]['text'], 'Archived novel'));
});

test('out-of-stock books are suggested and marked as out of stock', function () {
    $book = Book::factory()->create(['name' => 'Padma Nadir Majhi', 'status' => ProductStatusEnum::OutOfStock]);

    fakeGeminiJson(['reply' => 'It is out of stock right now.', 'suggestions' => [
        ['ref' => 1, 'reason' => 'A classic river novel.'],
    ]]);

    Livewire::test(Index::class)
        ->set('message', 'Padma Nadir Majhi')
        ->call('send')
        ->assertSet('messages.1.suggestions', [['id' => $book->id, 'reason' => 'A classic river novel.']])
        ->assertSee('Out of Stock');

    Http::assertSent(fn (Request $request): bool => str_contains($request->data()['system_instruction']['parts'][0]['text'], '"stock":"'.aiPrompt('product.document.out_of_stock').'"'));
});

test('a provider failure falls back to keyword matches', function () {
    $match = Book::factory()->create(['name' => 'Rice farming guide']);
    Book::factory()->create(['name' => 'Fish culture handbook']);
    Http::fake(['generativelanguage.googleapis.com/*' => Http::response([], 429)]);

    Livewire::test(Index::class)
        ->set('message', 'rice farming')
        ->call('send')
        ->assertHasNoErrors()
        ->assertSet('messages.1.text', aiPrompt('assistant.messages.resting_with_matches'))
        ->assertSet('messages.1.suggestions', [['id' => $match->id, 'reason' => '']])
        ->assertSee('Rice farming guide')
        ->assertDontSee('Fish culture handbook');
});

test('semantic search ranks candidates by embedding similarity', function () {
    config(['ai-assistant.providers.gemini.embedding_model' => 'test-embedding-model']);
    $far = Book::factory()->create(['name' => 'Far book']);
    $near = Book::factory()->create(['name' => 'Near book']);
    AiAssistantEmbedding::factory()->for($far, 'embeddable')->create(['vector' => [0.0, 1.0]]);
    AiAssistantEmbedding::factory()->for($near, 'embeddable')->create(['vector' => [1.0, 0.0]]);
    config(['ai-assistant.candidates' => 1]);

    Http::fake(['generativelanguage.googleapis.com/*:embedContent' => Http::response(['embedding' => ['values' => [0.9, 0.1]]])]);

    $candidates = app(AiAssistantSearchService::class)->candidates('something close', null);

    expect(array_column($candidates, 'id'))->toBe([$near->id]);
});

test('the embed command embeds only books whose content changed', function () {
    config(['ai-assistant.providers.gemini.embedding_model' => 'test-embedding-model']);
    $product = Book::factory()->create(['name' => 'Rice farming guide']);
    Http::fake(['generativelanguage.googleapis.com/*:embedContent' => Http::response(['embedding' => ['values' => [3.0, 4.0]]])]);

    $this->artisan('ai-assistant:embed', ['--sleep' => 0])->assertSuccessful();
    $this->artisan('ai-assistant:embed', ['--sleep' => 0])->assertSuccessful();

    Http::assertSentCount(1);
    expect($product->aiAssistantEmbedding()->sole()->vector)->toBe([0.6, 0.8]);

    $product->update(['name' => 'Rice farming guide, 2nd edition']);
    $this->artisan('ai-assistant:embed', ['--sleep' => 0])->assertSuccessful();

    Http::assertSentCount(2);
});

test('identical requests are answered from the cache', function () {
    fakeGeminiJson(['reply' => 'Cached answer.', 'suggestions' => []]);

    Livewire::test(Index::class)->set('message', 'Hello')->call('send')->assertSet('messages.1.text', 'Cached answer.');
    Livewire::test(Index::class)->set('message', 'Hello')->call('send')->assertSet('messages.1.text', 'Cached answer.');

    Http::assertSentCount(1);
});

test('emails and phone numbers are masked before reaching the provider', function () {
    fakeGeminiJson(['reply' => 'Thanks.', 'suggestions' => []]);

    Livewire::test(Index::class)->set('message', 'Mail me at rahim@example.com or call 01712-345678')->call('send');

    Http::assertSent(fn (Request $request): bool => $request->data()['contents'][0]['parts'][0]['text'] === 'Mail me at [email] or call [number]');
});

test('only models implementing the assistant interface get embeddings', function () {
    config(['ai-assistant.providers.gemini.embedding_model' => 'test-embedding-model']);
    Http::fake();

    $indexer = new class
    {
        use AiAssistantEmbeddingTrait;

        public function embed(Model $model): bool
        {
            return $this->syncAiAssistantEmbedding($model);
        }
    };

    expect($indexer->embed(Faq::factory()->create()))->toBeFalse();
    Http::assertNothingSent();
});

test('the reply language follows the script of the message', function () {
    fakeGeminiJson(['reply' => 'OK', 'suggestions' => []]);

    Livewire::test(Index::class)->set('message', 'Suggest a book')->call('send');
    Livewire::test(Index::class)->set('message', 'মাছ চাষের বই আছে?')->call('send');

    Http::assertSent(fn (Request $request): bool => str_contains($request->data()['system_instruction']['parts'][0]['text'], 'Always reply in English'));
    Http::assertSent(fn (Request $request): bool => str_contains($request->data()['system_instruction']['parts'][0]['text'], 'Always reply in Bangla'));
});

test('the message is required and length limited', function () {
    Http::fake();

    Livewire::test(Index::class)
        ->set('message', '')
        ->call('send')
        ->assertHasErrors(['message' => 'required'])
        ->set('message', str_repeat('a', aiAssistantMessageMaxLength() + 1))
        ->call('send')
        ->assertHasErrors(['message' => 'max']);

    Http::assertNothingSent();
});

test('the assistant is rate limited', function () {
    config(['throttle.assistant' => 1]);
    fakeGeminiJson(['reply' => 'Hi!', 'suggestions' => []]);

    Livewire::test(Index::class)
        ->set('message', 'One')
        ->call('send')
        ->assertHasNoErrors()
        ->set('message', 'Two')
        ->call('send')
        ->assertHasErrors('message')
        ->assertCount('messages', 2);
});

test('a disabled assistant is hidden and does not answer', function () {
    config(['ai-assistant.enabled' => false]);
    Http::fake();

    $this->get(route('home'))->assertOk()->assertDontSee(route('assistant'));
    $this->get(route('assistant'))->assertNotFound();

    Livewire::test(Index::class)
        ->set('message', 'Hello')
        ->call('send')
        ->assertCount('messages', 0);

    Http::assertNothingSent();
});

test('example prompts come from the config', function () {
    config(['ai-assistant.example_prompts' => ['Which books cover goat farming?']]);

    $this->get(route('assistant'))->assertOk()->assertSee('Which books cover goat farming?');
});

test('guests, customers and staff can all open the assistant page', function () {
    $this->get(route('home'))->assertSee(route('assistant'));
    $this->get(route('assistant'))->assertOk();
    $this->actingAs(User::factory()->create())->get(route('assistant'))->assertOk();

    $this->actingAs(User::factory()->admin()->create())->get(route('assistant'))->assertOk()->assertSee(route('assistant'));
});
