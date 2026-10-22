<?php

namespace App\Services\Frontend\AiAssistant;

use App\Enums\AiAssistant\AiAssistantRoleEnum;
use App\Resolvers\AiAssistant\AiAssistantProviderResolver;
use App\Traits\Security\ThrottleTrait;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class AiAssistantService
{
    use ThrottleTrait;

    public function __construct(
        private readonly AiAssistantProviderResolver $providerResolver,
        private readonly AiAssistantSearchService $searchService,
    ) {}

    /**
     * @param  list<array{role: string, text: string, suggestions?: list<array{id: int, reason: string}>}>  $history
     * @return array{reply: string, suggestions: list<array{id: int, reason: string}>, fallback: bool}
     */
    public function reply(array $history, string $message, string $throttleKey): array
    {
        $this->throttle("assistant:{$throttleKey}", throttleAttempts('assistant'), 'message', aiPrompt('assistant.messages.busy'));

        $message = $this->redact($message);
        $candidates = $this->searchService->candidates($message);

        try {
            $result = $this->providerResolver->chat()->generateJson($this->systemPrompt($candidates, $message), $this->messages($history, $message), $this->schema());
        } catch (ConnectionException|RequestException $exception) {
            report($exception);

            return $this->fallback($message);
        }

        $idsByRef = array_column($candidates, 'id', 'ref');
        $suggestions = array_values(collect(is_array($result['suggestions'] ?? null) ? $result['suggestions'] : [])
            ->filter(fn (mixed $item): bool => is_array($item) && isset($idsByRef[(int) ($item['ref'] ?? 0)]))
            ->map(fn (array $item): array => ['id' => (int) $idsByRef[(int) $item['ref']], 'reason' => Str::limit(trim((string) ($item['reason'] ?? '')), 160)])
            ->unique('id')
            ->take(aiAssistantResults())
            ->all());

        $reply = trim((string) ($result['reply'] ?? ''));

        if ($reply === '') {
            $reply = aiPrompt($suggestions ? 'assistant.messages.suggestions' : 'assistant.messages.no_answer');
        }

        return [
            'reply' => $reply,
            'suggestions' => $suggestions,
            'fallback' => false,
        ];
    }

    /**
     * @param  list<array{role: string, text: string, suggestions?: list<array{id: int, reason: string}>}>  $history
     * @return list<array{role: AiAssistantRoleEnum, text: string}>
     */
    private function messages(array $history, string $message): array
    {
        $history = array_slice($history, -aiAssistantHistoryMessages());
        $names = $this->searchService->products(array_merge(...array_map(fn (array $item): array => array_column($item['suggestions'] ?? [], 'id'), $history)))->pluck('name', 'id');

        return array_map(fn (array $item): array => [
            'role' => AiAssistantRoleEnum::from($item['role']),
            'text' => $this->redact($item['text']).(empty($item['suggestions']) ? '' : "\n".aiPrompt('assistant.history_suggested', ['names' => implode('; ', array_filter(array_map(fn (array $suggestion): ?string => $names[$suggestion['id']] ?? null, $item['suggestions'])))])),
        ], [...$history, ['role' => AiAssistantRoleEnum::User->value, 'text' => $message]]);
    }

    /**
     * @return array{reply: string, suggestions: list<array{id: int, reason: string}>, fallback: bool}
     */
    private function fallback(string $message): array
    {
        $ids = $this->searchService->matches($message, aiAssistantResults());

        return [
            'reply' => aiPrompt($ids ? 'assistant.messages.resting_with_matches' : 'assistant.messages.resting'),
            'suggestions' => array_map(fn (int $id): array => ['id' => $id, 'reason' => ''], $ids),
            'fallback' => true,
        ];
    }

    private function language(string $message): string
    {
        foreach (aiPromptMap('assistant.languages') as $script => $language) {
            if (preg_match("/\\p{{$script}}/u", $message)) {
                return $language;
            }
        }

        return aiPrompt('assistant.default_language');
    }

    private function redact(string $text): string
    {
        return (string) preg_replace(
            ['/[\w.+-]+@[\w-]+\.[\w.-]+/u', '/\+?[\d০-৯][\d০-৯\s-]{6,}[\d০-৯]/u'],
            [aiPrompt('assistant.redact.email'), aiPrompt('assistant.redact.number')],
            $text,
        );
    }

    /**
     * @param  list<array<string, scalar|null>>  $candidates
     */
    private function systemPrompt(array $candidates, string $message): string
    {
        return implode("\n", [
            aiPrompt('assistant.system', ['shop' => config('app.name'), 'results' => aiAssistantResults(), 'currency' => appCurrencySymbol(), 'language' => $this->language($message), 'today' => now()->toDateString()]),
            ...array_map(fn (array $candidate): string => (string) json_encode(Arr::except($candidate, 'id'), JSON_UNESCAPED_UNICODE), $candidates),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'reply' => ['type' => 'string'],
                'suggestions' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'ref' => ['type' => 'integer'],
                            'reason' => ['type' => 'string'],
                        ],
                        'required' => ['ref', 'reason'],
                    ],
                ],
            ],
            'required' => ['reply', 'suggestions'],
        ];
    }
}
