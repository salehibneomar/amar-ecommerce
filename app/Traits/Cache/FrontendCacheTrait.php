<?php

namespace App\Traits\Cache;

use App\Enums\System\FrontendCacheKeyEnum;
use Closure;
use Illuminate\Cache\Repository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

trait FrontendCacheTrait
{
    /**
     * @template TValue
     *
     * @param  Closure(): TValue  $callback
     * @return TValue
     */
    private function rememberFrontendCache(FrontendCacheKeyEnum $key, Closure $callback): mixed
    {
        return $this->rememberFlexibly($key->value, $callback);
    }

    /**
     * @template TValue
     *
     * @param  array<int|string, mixed>  $variant
     * @param  Closure(): TValue  $callback
     * @return TValue
     */
    private function rememberFrontendCacheVariant(FrontendCacheKeyEnum $key, array $variant, Closure $callback): mixed
    {
        if (! isFrontendCacheEnabled()) {
            return $callback();
        }

        $version = $this->frontendCache()->rememberForever($key->versionKey(), fn (): string => Str::random(8));

        return $this->rememberFlexibly($key->value.'.'.$version.'.'.md5((string) json_encode($variant)), $callback);
    }

    /**
     * @template TValue
     *
     * @param  Closure(): TValue  $callback
     * @return TValue
     */
    private function rememberFlexibly(string $key, Closure $callback): mixed
    {
        return isFrontendCacheEnabled() ? $this->frontendCache()->flexible($key, [frontendCacheTtl(), frontendCacheTtl() * 2], $callback) : $callback();
    }

    private function forgetFrontendCache(FrontendCacheKeyEnum ...$keys): void
    {
        foreach ($keys as $key) {
            $key->forget();
        }
    }

    /**
     * @return array{attributes: array<string, mixed>, relations: array<string, list<array<string, mixed>>>}
     */
    private function toFrontendCachePayload(Model $model, string ...$relations): array
    {
        return [
            'attributes' => $model->getAttributes(),
            'relations' => collect($relations)
                ->mapWithKeys(fn (string $relation): array => [$relation => $this->relationRows($model->getRelation($relation))])
                ->all(),
        ];
    }

    /**
     * @template TModel of Model
     *
     * @param  class-string<TModel>  $modelClass
     * @param  array{attributes: array<string, mixed>, relations: array<string, list<array<string, mixed>>>}  $payload
     * @return TModel
     */
    private function fromFrontendCachePayload(string $modelClass, array $payload): Model
    {
        $model = (new $modelClass)->newFromBuilder($payload['attributes']);

        foreach ($payload['relations'] as $relation => $rows) {
            $definition = $model->{$relation}();
            $hydrated = $definition->getRelated()->newQuery()->hydrate($rows);

            $model->setRelation($relation, $definition instanceof HasOne || $definition instanceof BelongsTo ? $hydrated->first() : $hydrated);
        }

        return $model;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function relationRows(mixed $related): array
    {
        if ($related instanceof Model) {
            return [$related->getAttributes()];
        }

        return $related === null ? [] : $related->map->getAttributes()->values()->all();
    }

    /**
     * @template TModel of Model
     *
     * @param  class-string<TModel>  $modelClass
     * @param  array<int, array{attributes: array<string, mixed>, relations: array<string, list<array<string, mixed>>>}>  $payloads
     * @return Collection<int, TModel>
     */
    private function collectFromFrontendCachePayloads(string $modelClass, array $payloads): Collection
    {
        return (new $modelClass)->newCollection(array_map(fn (array $payload): Model => $this->fromFrontendCachePayload($modelClass, $payload), $payloads));
    }

    private function frontendCache(): Repository
    {
        return Cache::store(frontendCacheStore());
    }
}
