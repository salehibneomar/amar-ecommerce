<?php

namespace App\Services\Frontend\AiAssistant;

use App\Enums\System\FrontendCacheKeyEnum;
use App\Models\AiAssistantEmbedding;
use App\Models\Category;
use App\Models\Product;
use App\Resolvers\AiAssistant\AiAssistantProviderResolver;
use App\Services\Frontend\Shop\PageService;
use App\Traits\Cache\FrontendCacheTrait;
use App\Traits\Query\CategoryTreeTrait;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;

class AiAssistantSearchService
{
    use CategoryTreeTrait, FrontendCacheTrait;

    /** @var array{products: array<int, Product>, categories: array<int, string>, texts: array<int, array{3: string, 2: string, 1: string}>}|null */
    private ?array $catalogue = null;

    /** @var array<string, array<int, list<float>>>|null */
    private ?array $vectors = null;

    public function __construct(
        private readonly PageService $pageService,
        private readonly AiAssistantProviderResolver $providerResolver,
    ) {}

    /**
     * @return list<array<string, scalar|null>>
     */
    public function candidates(string $query): array
    {
        $catalogue = $this->catalogue();
        $productIds = array_keys($catalogue['texts']);
        $ids = $this->semanticIds($query, (new Product)->getMorphClass(), $productIds) ?: $this->keywordIds($query);
        $ids = array_slice(array_values(array_unique([...$ids, ...$productIds])), 0, aiAssistantCandidates());

        return array_map(fn (int $ref, int $id): array => [
            'ref' => $ref + 1,
            'id' => $id,
            ...$catalogue['products'][$id]->aiAssistantDocument(),
            'category' => $catalogue['categories'][$catalogue['products'][$id]->category_id] ?? null,
        ], array_keys($ids), $ids);
    }

    /**
     * @return list<int>
     */
    public function matches(string $query, int $limit): array
    {
        return array_slice($this->keywordIds($query), 0, $limit);
    }

    /**
     * @param  list<int>  $ids
     * @return Collection<int, Product>
     */
    public function products(array $ids): Collection
    {
        return new Collection(array_intersect_key($this->catalogue()['products'], array_flip($ids)));
    }

    /**
     * @return array{products: array<int, Product>, categories: array<int, string>, texts: array<int, array{3: string, 2: string, 1: string}>}
     */
    private function catalogue(): array
    {
        if ($this->catalogue !== null) {
            return $this->catalogue;
        }

        $listed = $this->pageService->getListedProducts();
        $categories = $this->categoryNames(array_values(array_unique(array_filter($listed->pluck('category_id')->all()))));
        $products = [];
        $texts = [];

        foreach ($listed as $product) {
            $products[$product->id] = $product;
            $texts[$product->id] = [
                3 => mb_strtolower($product->name),
                2 => mb_strtolower(implode(' ', [...$product->aiAssistantAttributes(), $categories[$product->category_id] ?? ''])),
                1 => mb_strtolower(plainText($product->description)),
            ];
        }

        return $this->catalogue = ['products' => $products, 'categories' => $categories, 'texts' => $texts];
    }

    /**
     * @param  list<int>  $ids
     * @return list<int>
     */
    private function semanticIds(string $query, string $type, array $ids): array
    {
        $vectors = $this->vectors()[$type] ?? [];

        if ($vectors === []) {
            return [];
        }

        try {
            $queryVector = $this->providerResolver->resolve()->embed($query);
        } catch (ConnectionException|RequestException $exception) {
            report($exception);

            return [];
        }

        if (! $queryVector) {
            return [];
        }

        $scores = [];

        foreach ($ids as $id) {
            if (isset($vectors[$id]) && count($vectors[$id]) === count($queryVector)) {
                $scores[$id] = $this->dot($queryVector, $vectors[$id]);
            }
        }

        arsort($scores);

        return array_slice(array_keys($scores), 0, aiAssistantCandidates());
    }

    /**
     * @return list<int>
     */
    private function keywordIds(string $query): array
    {
        $words = array_values(array_diff(
            array_unique(array_filter(
                preg_split('/[^\p{L}\p{M}\p{N}]+/u', mb_strtolower($query)) ?: [],
                fn (string $word): bool => mb_strlen($word) >= 3,
            )),
            aiPromptList('assistant.stop_words'),
        ));

        if ($words === []) {
            return [];
        }

        $scores = [];

        foreach ($this->catalogue()['texts'] as $id => $fields) {
            $score = 0;

            foreach ($words as $word) {
                foreach ($fields as $weight => $field) {
                    $score += str_contains($field, $word) ? $weight : 0;
                }
            }

            if ($score > 0) {
                $scores[$id] = $score;
            }
        }

        arsort($scores);

        return array_keys($scores);
    }

    /**
     * @return array<string, array<int, list<float>>>
     */
    private function vectors(): array
    {
        $model = aiAssistantEmbeddingModel();

        if (! $model) {
            return [];
        }

        return $this->vectors ??= $this->rememberFrontendCache(
            FrontendCacheKeyEnum::AiAssistantVectors,
            fn (): array => AiAssistantEmbedding::query()
                ->where('model', $model)
                ->get(['embeddable_type', 'embeddable_id', 'vector'])
                ->groupBy('embeddable_type')
                ->map(fn (Collection $rows): array => $rows->pluck('vector', 'embeddable_id')->all())
                ->all(),
        );
    }

    /**
     * @param  list<int>  $categoryIds
     * @return array<int, string>
     */
    private function categoryNames(array $categoryIds): array
    {
        $paths = array_map(fn (array $path): array => array_slice(array_reverse($path), 0, categoryLevel()), $this->categoryPaths($categoryIds));
        $names = Category::query()->whereKey(array_merge([], ...array_values($paths)))->pluck('name', 'id');

        return array_map(fn (array $path): string => implode(' › ', array_map(fn (int $id): string => (string) $names[$id], $path)), $paths);
    }

    /**
     * @param  list<float>  $a
     * @param  list<float>  $b
     */
    private function dot(array $a, array $b): float
    {
        $sum = 0.0;

        foreach ($a as $index => $value) {
            $sum += $value * $b[$index];
        }

        return $sum;
    }
}
