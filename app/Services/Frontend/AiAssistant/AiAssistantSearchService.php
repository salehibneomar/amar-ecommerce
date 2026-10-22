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
        $ids = array_slice($this->rank($query, semantic: true), 0, aiAssistantTopMatches());

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
        return array_slice($this->rank($query, semantic: false), 0, $limit);
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
            $queryVector = $this->providerResolver->embedding()->embed($query);
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

        return array_keys($scores);
    }

    /**
     * @return list<int>
     */
    private function rank(string $query, bool $semantic): array
    {
        $suffixes = aiPromptList('assistant.word_suffixes');
        $text = $this->normalize($query);
        $words = $this->words($text, $suffixes);
        $filterWords = array_map(fn (array $list): array => array_map(fn (string $word): string => $this->stem(mb_strtolower($word), $suffixes), $list), aiPromptGroups('assistant.filters'));
        $filters = array_keys(array_filter($filterWords, fn (array $list): bool => array_intersect($words, $list) !== []));
        ['ranges' => $ranges, 'used' => $used] = $this->ranges($text, $suffixes);
        $terms = array_values(array_diff($words, array_merge(...array_values($filterWords)), $used));
        $ids = $terms === [] ? [] : (($semantic ? $this->semanticIds($query, (new Product)->getMorphClass(), array_keys($this->catalogue()['texts'])) : []) ?: $this->keywordIds($terms));

        if ($filters === [] && $ranges === []) {
            return $ids;
        }

        $filtered = $this->filteredIds($filters, $ranges);

        return $terms === [] ? $filtered : array_values(array_intersect($ids, $filtered));
    }

    /**
     * @param  list<string>  $suffixes
     * @return array{ranges: array<string, array{min: float|null, max: float|null}>, used: list<string>}
     */
    private function ranges(string $text, array $suffixes): array
    {
        $tokens = preg_split('/[^\p{L}\p{M}\p{N}]+/u', $text) ?: [];
        $stems = array_map(fn (string $token): string => $this->stem($token, $suffixes), $tokens);
        $ranges = [];
        $used = [];

        foreach (aiPromptKeys('assistant.ranges') as $name) {
            $triggers = array_map(fn (array $list): array => array_map(fn (string $word): string => $this->stem(mb_strtolower($word), $suffixes), $list), aiPromptGroups("assistant.ranges.{$name}"));
            $numbers = array_keys(array_filter($tokens, is_numeric(...)));
            $consumed = [];

            foreach ($numbers as $position => $index) {
                if (isset($consumed[$index])) {
                    continue;
                }

                $near = array_slice($stems, max(0, $index - 3), min(3, $index));

                foreach (['between', 'max', 'min'] as $bound) {
                    $trigger = array_values(array_intersect($near, $triggers[$bound] ?? []));

                    if ($trigger === []) {
                        continue;
                    }

                    $value = (float) $tokens[$index];
                    $next = $numbers[$position + 1] ?? null;
                    $ranges[$name] = match ($bound) {
                        'between' => ['min' => $value, 'max' => $next !== null ? (float) $tokens[$next] : null],
                        'max' => ['min' => $ranges[$name]['min'] ?? null, 'max' => $value],
                        'min' => ['min' => $value, 'max' => $ranges[$name]['max'] ?? null],
                    };
                    array_push($used, $trigger[0], $tokens[$index], ...($bound === 'between' && $next !== null ? [$tokens[$next]] : []));
                    $consumed += $bound === 'between' && $next !== null ? [$next => true] : [];

                    break;
                }
            }
        }

        return ['ranges' => $ranges, 'used' => array_values(array_unique($used))];
    }

    private function normalize(string $query): string
    {
        $text = mb_strtolower($query);

        foreach (aiPromptList('assistant.digits') as $digits) {
            $text = strtr($text, array_combine(mb_str_split($digits), array_map(strval(...), range(0, 9))));
        }

        return (string) preg_replace('/(?<=\d)[,.](?=\d{3}\b)/', '', $text);
    }

    /**
     * @param  list<string>  $suffixes
     * @return list<string>
     */
    private function words(string $text, array $suffixes): array
    {
        return array_values(array_unique(array_map(
            fn (string $word): string => $this->stem($word, $suffixes),
            array_diff(
                array_filter(preg_split('/[^\p{L}\p{M}\p{N}]+/u', $text) ?: [], fn (string $word): bool => mb_strlen($word) >= 3),
                aiPromptList('assistant.stop_words'),
            ),
        )));
    }

    /**
     * @param  list<string>  $filters
     * @param  array<string, array{min: float|null, max: float|null}>  $ranges
     * @return list<int>
     */
    private function filteredIds(array $filters, array $ranges): array
    {
        $sorts = [];

        foreach ($this->catalogue()['products'] as $id => $product) {
            $values = $product->aiAssistantFilterValues();
            $flags = array_map(fn (string $filter): int|float|null => $values[$filter] ?? null, $filters);
            $amounts = array_map(fn (string $name): int|float|null => $values[$name] ?? null, array_keys($ranges));

            if (in_array(null, [...$flags, ...$amounts], true)) {
                continue;
            }

            foreach (array_values($ranges) as $index => $range) {
                if (($range['min'] !== null && $amounts[$index] < $range['min']) || ($range['max'] !== null && $amounts[$index] > $range['max'])) {
                    continue 2;
                }
            }

            $sorts[$id] = [$flags, $amounts];
        }

        uasort($sorts, fn (array $a, array $b): int => ($b[0] <=> $a[0]) ?: ($a[1] <=> $b[1]));

        return array_keys($sorts);
    }

    /**
     * @param  list<string>  $terms
     * @return list<int>
     */
    private function keywordIds(array $terms): array
    {
        $patterns = array_map(fn (string $term): string => '/(?<![\p{L}\p{M}\p{N}])'.preg_quote($term, '/').'/u', $terms);
        $scores = [];

        foreach ($this->catalogue()['texts'] as $id => $fields) {
            $score = 0;

            foreach ($patterns as $pattern) {
                foreach ($fields as $weight => $field) {
                    $score += preg_match($pattern, $field) ? $weight : 0;
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
     * @param  list<string>  $suffixes
     */
    private function stem(string $word, array $suffixes): string
    {
        foreach ($suffixes as $suffix) {
            if (str_ends_with($word, $suffix) && mb_strlen($word) - mb_strlen($suffix) >= 3) {
                return mb_substr($word, 0, -mb_strlen($suffix));
            }
        }

        return $word;
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
