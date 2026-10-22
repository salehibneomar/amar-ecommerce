<?php

namespace App\Services\AiAssistant;

use App\Enums\AiAssistant\AiAssistantRoleEnum;
use App\Interfaces\AiAssistant\AiAssistantProviderInterface;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class AiAssistantOpenAiCompatibleService implements AiAssistantProviderInterface
{
    /**
     * @return list<float>|null
     */
    public function embed(string $text): ?array
    {
        $model = aiAssistantEmbeddingConfig('model');

        if (! $model) {
            return null;
        }

        $dimensions = aiAssistantEmbeddingConfig('dimensions');

        $values = $this->request(aiAssistantEmbeddingConfig(...))
            ->post('embeddings', array_filter([
                'model' => $model,
                'input' => $text,
                'dimensions' => is_numeric($dimensions) ? (int) $dimensions : null,
            ]))
            ->throw()
            ->json('data.0.embedding');

        return is_array($values) && $values !== [] ? array_map(floatval(...), array_values($values)) : null;
    }

    /**
     * @param  list<array{role: AiAssistantRoleEnum, text: string}>  $messages
     * @param  array<string, mixed>  $schema
     * @return array<string, mixed>
     */
    public function generateJson(string $system, array $messages, array $schema): array
    {
        $reasoning = aiAssistantChatConfig('reasoning');

        $text = $this->request(aiAssistantChatConfig(...))
            ->post('chat/completions', array_filter([
                'model' => aiAssistantChatConfig('model'),
                'messages' => [
                    ['role' => 'system', 'content' => $system],
                    ...array_map(fn (array $message): array => [
                        'role' => $message['role']->value,
                        'content' => $message['text'],
                    ], $messages),
                ],
                'response_format' => ['type' => 'json_schema', 'json_schema' => ['name' => 'reply', 'strict' => true, 'schema' => $this->strictSchema($schema)]],
                'max_completion_tokens' => aiAssistantMaxOutputTokens(),
                'reasoning_effort' => filled($reasoning) && ! is_numeric($reasoning) ? $reasoning : null,
            ]))
            ->throw()
            ->json('choices.0.message.content');

        $data = json_decode((string) $text, true);

        return is_array($data) ? $data : [];
    }

    /**
     * @param  array<string, mixed>  $schema
     * @return array<string, mixed>
     */
    private function strictSchema(array $schema): array
    {
        if (isset($schema['properties']) && is_array($schema['properties'])) {
            $schema['properties'] = array_map($this->strictSchema(...), $schema['properties']);
            $schema['additionalProperties'] = false;
        }

        if (isset($schema['items']) && is_array($schema['items'])) {
            $schema['items'] = $this->strictSchema($schema['items']);
        }

        return $schema;
    }

    /**
     * @param  callable(string): mixed  $config
     */
    private function request(callable $config): PendingRequest
    {
        $apiKey = (string) $config('api_key');

        return Http::baseUrl(rtrim((string) $config('url'), '/').'/')
            ->when($apiKey !== '', fn (PendingRequest $request): PendingRequest => $request->withToken($apiKey))
            ->timeout(aiAssistantTimeoutSeconds());
    }
}
