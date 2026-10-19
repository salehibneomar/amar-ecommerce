<?php

namespace App\Services\AiAssistant;

use App\Enums\AiAssistant\AiAssistantProviderEnum;
use App\Enums\AiAssistant\AiAssistantRoleEnum;
use App\Interfaces\AiAssistant\AiAssistantProviderInterface;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class AiAssistantGeminiService implements AiAssistantProviderInterface
{
    /**
     * @return list<float>|null
     */
    public function embed(string $text): ?array
    {
        $model = $this->config('embedding_model');

        if (! $model) {
            return null;
        }

        $values = $this->request()
            ->post($this->config('url')."/{$model}:embedContent", [
                'content' => ['parts' => [['text' => $text]]],
                'outputDimensionality' => $this->config('embedding_dimensions'),
            ])
            ->throw()
            ->json('embedding.values');

        return is_array($values) && $values !== [] ? array_map(floatval(...), array_values($values)) : null;
    }

    /**
     * @param  list<array{role: AiAssistantRoleEnum, text: string}>  $messages
     * @param  array<string, mixed>  $schema
     * @return array<string, mixed>
     */
    public function generateJson(string $system, array $messages, array $schema): array
    {
        $text = $this->request()
            ->post($this->config('url').'/'.$this->config('model').':generateContent', [
                'system_instruction' => ['parts' => [['text' => $system]]],
                'contents' => array_map(fn (array $message): array => [
                    'role' => $message['role'] === AiAssistantRoleEnum::Assistant ? 'model' : 'user',
                    'parts' => [['text' => $message['text']]],
                ], $messages),
                'generationConfig' => $this->generationConfig($schema),
            ])
            ->throw()
            ->json('candidates.0.content.parts.0.text');

        $data = json_decode((string) $text, true);

        return is_array($data) ? $data : [];
    }

    /**
     * @param  array<string, mixed>  $schema
     * @return array<string, mixed>
     */
    private function generationConfig(array $schema): array
    {
        $thinkingBudget = $this->config('thinking_budget');

        return array_filter([
            'responseMimeType' => 'application/json',
            'responseSchema' => $this->geminiSchema($schema),
            'maxOutputTokens' => aiAssistantMaxOutputTokens(),
            'thinkingConfig' => is_numeric($thinkingBudget) ? ['thinkingBudget' => (int) $thinkingBudget] : null,
        ]);
    }

    private function request(): PendingRequest
    {
        return Http::withHeaders(['x-goog-api-key' => $this->config('api_key')])->timeout(aiAssistantTimeoutSeconds());
    }

    private function config(string $key): mixed
    {
        return aiAssistantProviderConfig(AiAssistantProviderEnum::Gemini->value, $key);
    }

    /**
     * @param  array<string, mixed>  $schema
     * @return array<string, mixed>
     */
    private function geminiSchema(array $schema): array
    {
        return collect($schema)->map(fn (mixed $value, string $key): mixed => match (true) {
            $key === 'type' => strtoupper((string) $value),
            $key === 'properties' => array_map($this->geminiSchema(...), (array) $value),
            $key === 'items' => $this->geminiSchema((array) $value),
            default => $value,
        })->all();
    }
}
