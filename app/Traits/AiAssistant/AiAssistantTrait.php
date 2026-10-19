<?php

namespace App\Traits\AiAssistant;

use App\Models\AiAssistantEmbedding;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphOne;

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
     * @return MorphOne<AiAssistantEmbedding, $this>
     */
    public function aiAssistantEmbedding(): MorphOne
    {
        return $this->morphOne(AiAssistantEmbedding::class, 'embeddable');
    }
}
