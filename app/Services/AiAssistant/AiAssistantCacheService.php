<?php

namespace App\Services\AiAssistant;

use App\Enums\AiAssistant\AiAssistantRoleEnum;
use App\Interfaces\AiAssistant\AiAssistantProviderInterface;
use Closure;
use Illuminate\Support\Facades\Cache;

class AiAssistantCacheService implements AiAssistantProviderInterface
{
    public function __construct(
        private readonly AiAssistantProviderInterface $provider,
        private readonly string $prefix,
    ) {}

    /**
     * @return list<float>|null
     */
    public function embed(string $text): ?array
    {
        return $this->remember('embed', [$text], fn (): ?array => $this->provider->embed($text));
    }

    /**
     * @param  list<array{role: AiAssistantRoleEnum, text: string}>  $messages
     * @param  array<string, mixed>  $schema
     * @return array<string, mixed>
     */
    public function generateJson(string $system, array $messages, array $schema): array
    {
        $payload = [$system, array_map(fn (array $message): array => [$message['role']->value, $message['text']], $messages), $schema];

        return $this->remember('json', $payload, fn (): array => $this->provider->generateJson($system, $messages, $schema));
    }

    /**
     * @template TValue
     *
     * @param  array<int, mixed>  $payload
     * @param  Closure(): TValue  $callback
     * @return TValue
     */
    private function remember(string $type, array $payload, Closure $callback): mixed
    {
        $key = "ai-assistant.{$type}.".hash('sha256', $this->prefix.'|'.json_encode($payload));

        return Cache::store(frontendCacheStore())->remember($key, aiAssistantCacheMinutes() * 60, $callback);
    }
}
