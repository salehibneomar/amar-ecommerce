<?php

namespace App\Traits\AiAssistant;

use App\Enums\System\FrontendCacheKeyEnum;
use App\Interfaces\AiAssistant\AiAssistantInterface;
use App\Models\AiAssistantEmbedding;
use App\Resolvers\AiAssistant\AiAssistantProviderResolver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;

trait AiAssistantEmbeddingTrait
{
    private function syncAiAssistantEmbeddingLater(Model $model): void
    {
        if (! isAiAssistantEnabled() || ! aiAssistantEmbeddingModel() || ! $model instanceof AiAssistantInterface) {
            return;
        }

        defer(function () use ($model): void {
            try {
                $this->syncAiAssistantEmbedding($model->fresh() ?? $model);
            } catch (ConnectionException|RequestException $exception) {
                report($exception);
            }
        });
    }

    private function syncAiAssistantEmbedding(Model $model): bool
    {
        $embeddingModel = aiAssistantEmbeddingModel();

        if (! $embeddingModel || ! $model instanceof AiAssistantInterface) {
            return false;
        }

        $text = $model->aiAssistantEmbeddingText();
        $hash = hash('sha256', $embeddingModel.'|'.$text);
        $current = $model->getRelationValue('aiAssistantEmbedding');

        if ($current instanceof AiAssistantEmbedding && $current->model === $embeddingModel && $current->content_hash === $hash) {
            return false;
        }

        $vector = app(AiAssistantProviderResolver::class)->resolve(cached: false)->embed($text);

        if (! $vector) {
            return false;
        }

        $length = sqrt(array_sum(array_map(fn (float $value): float => $value * $value, $vector))) ?: 1.0;

        AiAssistantEmbedding::query()->updateOrCreate(
            ['embeddable_type' => $model->getMorphClass(), 'embeddable_id' => $model->getKey()],
            ['model' => $embeddingModel, 'content_hash' => $hash, 'vector' => array_map(fn (float $value): float => $value / $length, $vector)],
        );

        Cache::store(frontendCacheStore())->forget(FrontendCacheKeyEnum::AiAssistantVectors->value);

        return true;
    }
}
