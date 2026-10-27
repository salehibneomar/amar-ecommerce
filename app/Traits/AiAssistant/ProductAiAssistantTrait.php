<?php

namespace App\Traits\AiAssistant;

use App\Enums\Catalog\ProductStatusEnum;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * @method static Builder<static> query()
 *
 * @mixin Product
 */
trait ProductAiAssistantTrait
{
    use AiAssistantTrait;

    /**
     * @return Builder<static>
     */
    public static function aiAssistantEmbeddingQuery(): Builder
    {
        return static::query()
            ->with(['category.parent.parent', 'aiAssistantEmbedding'])
            ->whereIn('status', ProductStatusEnum::listed());
    }

    /**
     * @return list<string>
     */
    protected function aiAssistantColumns(): array
    {
        return [];
    }

    /**
     * @return array<string, scalar|null>
     */
    public function aiAssistantAttributes(): array
    {
        return $this->only($this->aiAssistantColumns());
    }

    /**
     * @return array<string, scalar|null>
     */
    public function aiAssistantDocument(): array
    {
        return [
            'name' => $this->name,
            ...$this->aiAssistantAttributes(),
            'price' => formatMoney($this->final_price),
            ...($this->has_discount ? ['discount' => "{$this->discount_percentage}%", 'regular_price' => formatMoney($this->price)] : []),
            'added' => $this->created_at->toDateString(),
            'about' => Str::limit(plainText($this->description), 200),
            ...($this->status->isInStock() ? [] : ['stock' => aiPrompt('product.document.out_of_stock')]),
        ];
    }

    /**
     * @return array<string, int|float|null>
     */
    public function aiAssistantFilterValues(): array
    {
        return [
            'on_sale' => $this->has_discount ? $this->discount_percentage : null,
            'new' => $this->created_at->getTimestamp(),
            'price' => $this->final_price,
        ];
    }

    public function aiAssistantEmbeddingText(): string
    {
        $names = [];

        for ($node = $this->category; $node !== null && count($names) < categoryLevel(); $node = $node->relationLoaded('parent') ? $node->parent : null) {
            array_unshift($names, $node->name);
        }

        $category = $names === [] ? null : implode(' › ', $names);
        $attributes = array_filter($this->aiAssistantAttributes(), filled(...));

        return implode("\n", array_filter([
            $this->name,
            ...array_map(fn (string $column, mixed $value): string => aiPrompt("product.embedding.{$column}", [$column => $value]), array_keys($attributes), $attributes),
            $category ? aiPrompt('product.embedding.category', ['category' => $category]) : null,
            Str::limit(plainText($this->description), 500),
        ]));
    }
}
