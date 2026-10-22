<?php

namespace App\Traits\AiAssistant;

use App\Models\AiAssistantEmbedding;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * @method static Builder<static> query()
 * @method MorphOne<AiAssistantEmbedding, $this> morphOne(string $related, string $name, ?string $type = null, ?string $id = null, ?string $localKey = null)
 */
trait AiAssistantTrait
{
    /**
     * @return Builder<static>
     */
    public static function aiAssistantEmbeddingQuery(): Builder
    {
        return static::query();
    }

    /**
     * @return array<string, int|float|null>
     */
    public function aiAssistantFilterValues(): array
    {
        return [];
    }

    /**
     * @return MorphOne<AiAssistantEmbedding, $this>
     */
    public function aiAssistantEmbedding(): MorphOne
    {
        return $this->morphOne(AiAssistantEmbedding::class, 'embeddable');
    }
}
