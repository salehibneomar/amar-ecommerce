<?php

namespace App\Livewire\Concerns;

use App\Services\Backoffice\Catalog\CategoryService;

trait InteractsWithCategoryLevels
{
    protected CategoryService $categoryLevelService;

    public function bootInteractsWithCategoryLevels(CategoryService $categoryService): void
    {
        $this->categoryLevelService = $categoryService;
    }

    public function updatedInteractsWithCategoryLevels(string $name): void
    {
        [$property, $level] = array_pad(explode('.', $name, 2), 2, null);

        if ($level === null || ! array_key_exists($property, $this->categoryLevelProperties())) {
            return;
        }

        $this->{$property} = array_slice($this->{$property}, 0, (int) $level + 1);
        $this->{$property.'Limits'} = array_slice($this->{$property.'Limits'}, 0, (int) $level + 1, true);
        $this->categoryLevelsUpdated($property);
    }

    public function loadMoreCategoryLevel(string $property, int $level): void
    {
        $page = $this->categoryLevelProperties()[$property] ?? null;

        if ($page !== null) {
            $this->{$property.'Limits'}[$level] = ($this->{$property.'Limits'}[$level] ?? $page) + $page;
        }
    }

    /**
     * @return list<array{options: array<int, string>, has_more: bool, disabled: bool}>
     */
    protected function levelOptionsFor(string $property, int $levels): array
    {
        $path = $this->{$property};
        $result = [];

        for ($level = 0; $level < $levels; $level++) {
            $parentId = $level === 0 ? null : (filled($path[$level - 1] ?? null) ? (int) $path[$level - 1] : null);
            $batch = $level > 0 && $parentId === null
                ? ['options' => [], 'has_more' => false]
                : $this->categoryLevelService->getCategoryOptionsBatch(
                    $this->{$property.'Limits'}[$level] ?? $this->categoryLevelProperties()[$property],
                    filled($path[$level] ?? null) ? (int) $path[$level] : null,
                    $parentId,
                );

            $result[] = [...$batch, 'disabled' => $level > 0 && $batch['options'] === []];
        }

        return $result;
    }

    protected function selectedCategoryLevelId(string $property): ?int
    {
        $selected = array_filter($this->{$property}, filled(...));

        return $selected === [] ? null : (int) end($selected);
    }

    /**
     * @param  list<int>  $ids
     */
    protected function setCategoryLevels(string $property, array $ids): void
    {
        $this->{$property} = array_map(strval(...), $ids);
        $this->{$property.'Limits'} = [];
    }

    protected function categoryLevelsUpdated(string $property): void {}

    /**
     * @return array<string, int>
     */
    abstract protected function categoryLevelProperties(): array;
}
