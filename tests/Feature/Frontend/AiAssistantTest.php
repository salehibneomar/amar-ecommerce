<?php

use App\Enums\Catalog\ProductStatusEnum;
use App\Livewire\Frontend\AiAssistant\Index;
use App\Models\AiAssistantEmbedding;
use App\Models\Faq;
use App\Models\Product;
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
        'ai-assistant.chat' => ['driver' => 'gemini', 'url' => 'https://generativelanguage.googleapis.com/v1beta/models', 'api_key' => 'test-key', 'model' => 'test-model', 'reasoning' => null],
        'ai-assistant.embedding' => ['driver' => 'gemini', 'url' => 'https://generativelanguage.googleapis.com/v1beta/models', 'api_key' => 'test-key', 'model' => null, 'dimensions' => null],
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
    fakeGeminiJson(['reply' => 'Try our cotton t-shirts.', 'suggestions' => []]);

    Livewire::test(Index::class)
        ->set('messages', [
            ['role' => 'user', 'text' => 'Hi', 'suggestions' => []],
            ['role' => 'assistant', 'text' => 'Hello!', 'suggestions' => []],
        ])
        ->set('message', 'Suggest a t-shirt')
        ->call('send')
        ->assertHasNoErrors()
        ->assertSet('message', '')
        ->assertSet('messages.2', ['role' => 'user', 'text' => 'Suggest a t-shirt', 'suggestions' => []])
        ->assertSet('messages.3', ['role' => 'assistant', 'text' => 'Try our cotton t-shirts.', 'suggestions' => []]);

    Http::assertSent(fn (Request $request): bool => str_contains($request->url(), 'test-model:generateContent')
        && $request->hasHeader('x-goog-api-key', 'test-key')
        && $request->data()['contents'][1]['role'] === 'model'
        && $request->data()['contents'][2]['parts'][0]['text'] === 'Suggest a t-shirt'
        && $request->data()['generationConfig']['responseSchema']['type'] === 'OBJECT'
        && $request->data()['generationConfig']['maxOutputTokens'] === aiAssistantMaxOutputTokens()
        && ! isset($request->data()['generationConfig']['thinkingConfig']));
});

test('suggestions come only from listed candidates and show database prices', function () {
    $tshirt = Product::factory()->create(['name' => 'Cotton T-Shirt', 'price' => 400, 'discount_percentage' => 25]);
    Product::factory()->archived()->create(['name' => 'Archived t-shirt']);

    fakeGeminiJson(['reply' => 'You may like these.', 'suggestions' => [
        ['ref' => 1, 'reason' => 'Soft cotton for everyday wear.'],
        ['ref' => 2, 'reason' => 'Not a candidate.'],
        ['ref' => 99, 'reason' => 'Invented.'],
    ]]);

    Livewire::test(Index::class)
        ->set('message', 'A soft cotton t-shirt')
        ->call('send')
        ->assertSet('messages.1.suggestions', [['id' => $tshirt->id, 'reason' => 'Soft cotton for everyday wear.']])
        ->assertSee('Cotton T-Shirt')
        ->assertSee(formatMoney(300))
        ->assertDontSee('Archived t-shirt');

    Http::assertSent(fn (Request $request): bool => str_contains($request->data()['system_instruction']['parts'][0]['text'], '"ref":1,"name":"Cotton T-Shirt"')
        && ! str_contains($request->data()['system_instruction']['parts'][0]['text'], '"id"')
        && ! str_contains($request->data()['system_instruction']['parts'][0]['text'], 'Archived t-shirt'));
});

test('candidates show the date added and the discount so the model can answer about new arrivals and sales', function () {
    $this->travelTo('2026-10-22 10:00:00');
    Product::factory()->create(['name' => 'Cotton T-Shirt', 'price' => 400, 'discount_percentage' => 25, 'created_at' => '2026-10-20 09:00:00']);
    Product::factory()->create(['name' => 'Ceramic Mug', 'discount_percentage' => null]);
    fakeGeminiJson(['reply' => 'Here you go.', 'suggestions' => []]);

    Livewire::test(Index::class)->set('message', 'Cotton t-shirt or ceramic mug')->call('send');

    Http::assertSent(function (Request $request): bool {
        $system = $request->data()['system_instruction']['parts'][0]['text'];

        return str_contains($system, 'Today is 2026-10-22.')
            && str_contains($system, '"discount":"25%","regular_price":'.json_encode(formatMoney(400), JSON_UNESCAPED_UNICODE).',"added":"2026-10-20"')
            && preg_match('/"name":"Ceramic Mug","price":"[^"]+","added":"2026-10-22"/u', $system) === 1;
    });
});

test('out-of-stock products are suggested and marked as out of stock', function () {
    $product = Product::factory()->create(['name' => 'Cotton T-Shirt', 'status' => ProductStatusEnum::OutOfStock]);

    fakeGeminiJson(['reply' => 'It is out of stock right now.', 'suggestions' => [
        ['ref' => 1, 'reason' => 'Soft cotton for everyday wear.'],
    ]]);

    Livewire::test(Index::class)
        ->set('message', 'Cotton T-Shirt')
        ->call('send')
        ->assertSet('messages.1.suggestions', [['id' => $product->id, 'reason' => 'Soft cotton for everyday wear.']])
        ->assertSee('Out of Stock');

    Http::assertSent(fn (Request $request): bool => str_contains($request->data()['system_instruction']['parts'][0]['text'], '"stock":"'.aiPrompt('product.document.out_of_stock').'"'));
});

test('a provider failure falls back to keyword matches', function () {
    $match = Product::factory()->create(['name' => 'Lightweight running shoes']);
    Product::factory()->create(['name' => 'Ceramic coffee mug']);
    Http::fake(['generativelanguage.googleapis.com/*' => Http::response([], 429)]);

    Livewire::test(Index::class)
        ->set('message', 'running shoes')
        ->call('send')
        ->assertHasNoErrors()
        ->assertSet('messages.1.text', aiPrompt('assistant.messages.resting_with_matches'))
        ->assertSet('messages.1.suggestions', [['id' => $match->id, 'reason' => '']])
        ->assertSee('Lightweight running shoes')
        ->assertDontSee('Ceramic coffee mug');
});

test('only real matches are sent to the AI, never filler products', function () {
    $match = Product::factory()->create(['name' => 'Leather wallet', 'description' => 'Slim wallet.']);
    Product::factory()->create(['name' => 'Ceramic mug', 'description' => 'For coffee.']);

    expect(array_column(app(AiAssistantSearchService::class)->candidates('leather wallet'), 'id'))->toBe([$match->id])
        ->and(app(AiAssistantSearchService::class)->candidates('spaceship'))->toBe([]);
});

test('sale questions match only discounted products, biggest discount first', function () {
    $small = Product::factory()->create(['name' => 'Cotton shirt', 'discount_percentage' => 10]);
    $big = Product::factory()->create(['name' => 'Leather wallet', 'discount_percentage' => 30]);
    Product::factory()->create(['name' => 'Cotton socks', 'discount_percentage' => null]);
    $search = app(AiAssistantSearchService::class);

    expect(array_column($search->candidates('Which products are on sale?'), 'id'))->toBe([$big->id, $small->id])
        ->and(array_column($search->candidates('cotton on sale'), 'id'))->toBe([$small->id])
        ->and($search->matches('any discounts?', 5))->toBe([$big->id, $small->id]);
});

test('price questions match only products in the range, cheapest first', function () {
    $mug = Product::factory()->create(['name' => 'Ceramic mug', 'price' => 450, 'discount_percentage' => null]);
    $shirt = Product::factory()->create(['name' => 'Cotton shirt', 'price' => 1000, 'discount_percentage' => 10]);
    $lamp = Product::factory()->create(['name' => 'Table lamp', 'price' => 2300, 'discount_percentage' => null]);
    $search = app(AiAssistantSearchService::class);

    expect(array_column($search->candidates('show products under $1,000'), 'id'))->toBe([$mug->id, $shirt->id])
        ->and(array_column($search->candidates('anything less than ১০০০ taka'), 'id'))->toBe([$mug->id, $shirt->id])
        ->and(array_column($search->candidates('over 500'), 'id'))->toBe([$shirt->id, $lamp->id])
        ->and(array_column($search->candidates('between 500 and 2000'), 'id'))->toBe([$shirt->id])
        ->and(array_column($search->candidates('shirt under 1000'), 'id'))->toBe([$shirt->id])
        ->and(array_column($search->candidates('on sale under 500'), 'id'))->toBe([]);
});

test('new arrival questions match the newest products first', function () {
    config(['ai-assistant.top_matches' => 2]);
    Product::factory()->create(['name' => 'Old mug', 'created_at' => now()->subMonth()]);
    $newer = Product::factory()->create(['name' => 'Newer lamp', 'created_at' => now()->subDay()]);
    $newest = Product::factory()->create(['name' => 'Newest pan', 'created_at' => now()]);

    expect(array_column(app(AiAssistantSearchService::class)->candidates('What is new this week?'), 'id'))->toBe([$newest->id, $newer->id]);
});

test('keyword matches start at a word and ignore a plural s', function () {
    $clothing = Product::factory()->create(['name' => 'Kids clothing set', 'description' => 'Soft cotton set.']);
    Product::factory()->create(['name' => 'Daily face wash', 'description' => 'Gentle skincare for every day.']);

    expect(app(AiAssistantSearchService::class)->matches('what cloths are you selling now?', 5))->toBe([$clothing->id]);
});

test('semantic search ranks candidates by embedding similarity', function () {
    config(['ai-assistant.embedding.model' => 'test-embedding-model']);
    $far = Product::factory()->create(['name' => 'Far product']);
    $near = Product::factory()->create(['name' => 'Near product']);
    AiAssistantEmbedding::factory()->for($far, 'embeddable')->create(['vector' => [0.0, 1.0]]);
    AiAssistantEmbedding::factory()->for($near, 'embeddable')->create(['vector' => [1.0, 0.0]]);
    config(['ai-assistant.top_matches' => 1]);

    Http::fake(['generativelanguage.googleapis.com/*:embedContent' => Http::response(['embedding' => ['values' => [0.9, 0.1]]])]);

    $candidates = app(AiAssistantSearchService::class)->candidates('something close');

    expect(array_column($candidates, 'id'))->toBe([$near->id]);
});

test('an openai-compatible provider answers through chat completions', function () {
    config([
        'ai-assistant.chat' => ['driver' => 'openai', 'url' => 'https://api.groq.com/openai/v1', 'api_key' => 'groq-key', 'model' => 'groq-model', 'reasoning' => 'low'],
    ]);

    Http::fake(['api.groq.com/*' => Http::response([
        'choices' => [['message' => ['content' => json_encode(['reply' => 'Try our cotton t-shirts.', 'suggestions' => []])]]],
    ])]);

    Livewire::test(Index::class)
        ->set('message', 'Suggest a t-shirt')
        ->call('send')
        ->assertSet('messages.1.text', 'Try our cotton t-shirts.');

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.groq.com/openai/v1/chat/completions'
        && $request->hasHeader('Authorization', 'Bearer groq-key')
        && $request->data()['model'] === 'groq-model'
        && $request->data()['messages'][0]['role'] === 'system'
        && $request->data()['messages'][1] === ['role' => 'user', 'content' => 'Suggest a t-shirt']
        && $request->data()['response_format']['json_schema']['strict'] === true
        && $request->data()['response_format']['json_schema']['schema']['additionalProperties'] === false
        && $request->data()['response_format']['json_schema']['schema']['properties']['suggestions']['items']['additionalProperties'] === false
        && $request->data()['max_completion_tokens'] === aiAssistantMaxOutputTokens()
        && $request->data()['reasoning_effort'] === 'low');
});

test('the chat reasoning setting maps to the gemini thinking budget or level', function (string $reasoning, array $thinkingConfig) {
    config(['ai-assistant.chat.reasoning' => $reasoning]);
    fakeGeminiJson(['reply' => 'Hi', 'suggestions' => []]);

    Livewire::test(Index::class)->set('message', 'Hello')->call('send');

    Http::assertSent(fn (Request $request): bool => $request->data()['generationConfig']['thinkingConfig'] === $thinkingConfig);
})->with([
    'budget' => ['0', ['thinkingBudget' => 0]],
    'level' => ['low', ['thinkingLevel' => 'low']],
]);

test('embeddings can use a different driver and provider than chat', function () {
    config(['ai-assistant.embedding' => ['driver' => 'openai', 'url' => 'https://api.openai.com/v1', 'api_key' => 'openai-key', 'model' => 'test-embedding-model', 'dimensions' => 2]]);
    $product = Product::factory()->create(['name' => 'Near product']);

    Http::fake(['api.openai.com/*' => Http::response(['data' => [['embedding' => [3.0, 4.0]]]])]);

    $this->artisan('ai-assistant:embed')->assertSuccessful();

    expect($product->aiAssistantEmbedding()->value('model'))->toBe('test-embedding-model');
    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.openai.com/v1/embeddings'
        && $request->data() === ['model' => 'test-embedding-model', 'input' => $product->aiAssistantEmbeddingText(), 'dimensions' => 2]);
});

test('the embed command embeds only products whose content changed', function () {
    config(['ai-assistant.embedding.model' => 'test-embedding-model']);
    $product = Product::factory()->create(['name' => 'Lightweight running shoes']);
    Http::fake(['generativelanguage.googleapis.com/*:embedContent' => Http::response(['embedding' => ['values' => [3.0, 4.0]]])]);

    $this->artisan('ai-assistant:embed', ['--sleep' => 0])->assertSuccessful();
    $this->artisan('ai-assistant:embed', ['--sleep' => 0])->assertSuccessful();

    Http::assertSentCount(1);
    expect($product->aiAssistantEmbedding()->sole()->vector)->toBe([0.6, 0.8]);

    $product->update(['name' => 'Lightweight running shoes, 2nd generation']);
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
    config(['ai-assistant.embedding.model' => 'test-embedding-model']);
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

    Livewire::test(Index::class)->set('message', 'Suggest a product')->call('send');
    Livewire::test(Index::class)->set('message', 'রান্নার হাঁড়ি আছে?')->call('send');

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

test('the assistant stays hidden without a chat url', function () {
    config(['ai-assistant.chat.url' => null]);

    $this->get(route('assistant'))->assertNotFound();
});

test('example prompts come from the config', function () {
    config(['ai-assistant.example_prompts' => ['Which products are on sale?']]);

    $this->get(route('assistant'))->assertOk()->assertSee('Which products are on sale?');
});

test('guests, customers and staff can all open the assistant page', function () {
    $this->get(route('home'))->assertSee(route('assistant'));
    $this->get(route('assistant'))->assertOk();
    $this->actingAs(User::factory()->create())->get(route('assistant'))->assertOk();

    $this->actingAs(User::factory()->admin()->create())->get(route('assistant'))->assertOk()->assertSee(route('assistant'));
});
