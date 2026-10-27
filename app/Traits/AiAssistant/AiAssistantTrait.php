<?php

namespace App\Traits\AiAssistant;

use App\Models\AiAssistantEmbedding;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * @method static Builder<static> query()
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
