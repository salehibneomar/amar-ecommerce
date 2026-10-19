<?php

namespace App\Traits\Query;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait TableQueryTrait
{
    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @param  list<string>  $searchColumns
     * @param  array<string, mixed>  $filters
     * @param  list<string>  $sortableColumns
     * @return LengthAwarePaginator<int, TModel>
     */
    protected function paginateTableQuery(
        Builder $query,
        string $search,
        array $searchColumns,
        array $filters,
        int $perPage,
        string $sortBy,
        string $sortDirection,
        array $sortableColumns,
        string $defaultSortBy = 'created_at',
        string $defaultSortDirection = 'desc',
        string $tieBreaker = 'id',
    ): LengthAwarePaginator {
        $query = $this->applySearch($query, $search, $searchColumns);
        $query = $this->applyFilters($query, $filters);
        $query = $this->applySorting(
            $query,
            $sortBy,
            $sortDirection,
            $sortableColumns,
            $defaultSortBy,
            $defaultSortDirection,
        );

        if ($sortBy !== $tieBreaker) {
            $query->orderByDesc($tieBreaker);
        }

        return $query->paginate(max(1, $perPage));
    }

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @param  list<string>  $columns
     * @return Builder<TModel>
     */
    private function applySearch(
        Builder $query,
        string $search,
        array $columns,
    ): Builder {
        $search = trim($search);

        if ($search === '' || $columns === []) {
            return $query;
        }

        return $query->where(function (Builder $query) use (
            $search,
            $columns,
        ): void {
            foreach ($columns as $index => $column) {
                $method = $index === 0 ? 'where' : 'orWhere';

                $query->{$method}($column, 'like', "%{$search}%");
            }
        });
    }

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @param  array<string, mixed>  $filters
     * @return Builder<TModel>
     */
    private function applyFilters(Builder $query, array $filters): Builder
    {
        foreach ($filters as $column => $value) {
            if ($value !== null && $value !== '') {
                $query->where($column, $value);
            }
        }

        return $query;
    }

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @param  list<string>  $allowedColumns
     * @return Builder<TModel>
     */
    private function applySorting(
        Builder $query,
        string $column,
        string $direction,
        array $allowedColumns,
        string $defaultColumn,
        string $defaultDirection,
    ): Builder {
        $column = in_array($column, $allowedColumns, true)
            ? $column
            : $defaultColumn;
        $direction = in_array($direction, ['asc', 'desc'], true)
            ? $direction
            : $defaultDirection;

        return $query->orderBy($column, $direction === 'asc' ? 'asc' : 'desc');
    }
}
