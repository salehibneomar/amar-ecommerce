<?php

namespace App\Traits\Query;

use App\Models\Category;

trait CategoryTreeTrait
{
    /**
     * @param  array<int, int|null>  $categoryIds
     * @return array<int, list<int>>
     */
    private function categoryPaths(array $categoryIds): array
    {
        $categoryIds = array_values(array_unique(array_filter($categoryIds)));
        $parents = [];
        $pending = $categoryIds;

        while ($pending !== []) {
            $found = Category::query()->whereKey($pending)->pluck('parent_id', 'id')->all();
            $parents += $found;
            $pending = array_values(array_diff(array_filter($found), array_keys($parents)));
        }

        $paths = [];

        foreach ($categoryIds as $id) {
            $path = [];

            for ($current = $id; $current !== null && array_key_exists($current, $parents) && ! in_array($current, $path, true); $current = $parents[$current]) {
                $path[] = $current;
            }

            $paths[$id] = $path;
        }

        return $paths;
    }

    /**
     * @return list<int>
     */
    private function categoryLevelIds(?int $id): array
    {
        return $id === null ? [] : array_slice(array_reverse($this->categoryPaths([$id])[$id] ?? []), 0, categoryLevel());
    }

    /**
     * @param  list<int>  $categoryIds
     * @return list<int>
     */
    private function categoriesWithDescendantIds(array $categoryIds, ?int $maxDepth = null): array
    {
        $ids = array_values(array_unique($categoryIds));
        $frontier = $ids;

        while ($frontier !== []) {
            $frontier = array_values(array_diff(Category::query()
                ->whereIn('parent_id', $frontier)
                ->when($maxDepth !== null, fn ($query) => $query->where('depth', '<=', $maxDepth))
                ->pluck('id')
                ->all(), $ids));
            $ids = [...$ids, ...$frontier];
        }

        return $ids;
    }

    /**
     * @param  array<int, int|null>  $categoryIds
     * @return list<int>
     */
    private function rootCategoryIds(array $categoryIds): array
    {
        return array_values(array_unique(array_map(
            fn (array $path): int => $path[array_key_last($path)],
            array_filter($this->categoryPaths($categoryIds)),
        )));
    }
}
