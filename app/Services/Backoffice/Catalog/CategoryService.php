<?php

namespace App\Services\Backoffice\Catalog;

use App\Enums\Catalog\CategoryStatusEnum;
use App\Models\Category;
use App\Models\Product;
use App\Traits\Query\CategoryTreeTrait;
use App\Traits\Query\TableQueryTrait;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CategoryService
{
    use CategoryTreeTrait, TableQueryTrait;

    /**
     * @return list<string>
     */
    public function sortableColumns(): array
    {
        return ['id', 'name', 'status', 'sort_order', 'created_at'];
    }

    /**
     * @return LengthAwarePaginator<int, Category>
     */
    public function getCategories(
        string $search = '',
        int $perPage = 10,
        ?CategoryStatusEnum $status = null,
        ?int $parentId = null,
        bool $rootOnly = false,
        string $sortBy = 'created_at',
        string $sortDirection = 'desc',
    ): LengthAwarePaginator {
        $query = Category::query()->with(['parent', 'media']);

        if ($rootOnly) {
            $query->whereNull('parent_id');
        } elseif ($parentId !== null) {
            $query->where('parent_id', $parentId);
        }

        return $this->paginateTableQuery(
            query: $query,
            search: $search,
            searchColumns: ['name'],
            filters: ['status' => $status],
            perPage: $perPage,
            sortBy: $sortBy,
            sortDirection: $sortDirection,
            sortableColumns: $this->sortableColumns(),
        );
    }

    public function getCategoryById(int|string $id): Category
    {
        return Category::findOrFail($id);
    }

    /**
     * @return array{options: array<int, string>, has_more: bool}
     */
    public function getCategoryOptionsBatch(int $limit, ?int $selectedId = null, ?int $parentId = null): array
    {
        $parents = Category::query()
            ->where('parent_id', $parentId)
            ->orderBy('name')
            ->limit($limit + 1)
            ->pluck('name', 'id');

        $options = $parents->take($limit)->all();

        if ($selectedId !== null && ! array_key_exists($selectedId, $options)) {
            $selected = Category::query()->where('parent_id', $parentId)->whereKey($selectedId)->value('name');

            if ($selected !== null) {
                $options[$selectedId] = $selected;
            }
        }

        return ['options' => $options, 'has_more' => $parents->count() > $limit];
    }

    /**
     * @param  list<int>  $exceptIds
     * @return array<int, string>
     */
    public function searchRootOptions(string $search, array $exceptIds = [], int $limit = 8): array
    {
        $matches = Category::query()->where('name', 'like', '%'.trim($search).'%')->limit(50)->pluck('id')->all();

        return $this->labelled(Category::query()
            ->whereKey($this->rootCategoryIds($matches))
            ->whereKeyNot($exceptIds)
            ->orderBy('name')
            ->limit($limit));
    }

    /**
     * @param  list<int>  $ids
     * @return array<int, string>
     */
    public function getOptionsByIds(array $ids): array
    {
        return $ids === [] ? [] : $this->labelled(Category::query()->whereKey($ids)->orderBy('name'));
    }

    /**
     * @return list<int>
     */
    public function getCategoryLevelIds(?int $id): array
    {
        return $this->categoryLevelIds($id);
    }

    public function getCategoryLabel(?int $id): ?string
    {
        $ids = array_reverse($this->getCategoryLevelIds($id));
        $names = Category::query()->whereKey($ids)->pluck('name', 'id');

        return $ids === [] ? null : implode(' > ', array_map(fn (int $id): string => (string) $names[$id], $ids));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Category
    {
        $data['depth'] = $this->depthUnder(null, $data['parent_id'] ?? null);

        $category = Category::create($data);

        return $category;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int|string $id, array $data): bool
    {
        $category = $this->getCategoryById($id);
        $data['depth'] = $this->depthUnder($category, $data['parent_id'] ?? null);
        $shift = $data['depth'] - $category->depth;

        $updated = DB::transaction(function () use ($category, $data, $shift): bool {
            if ($shift !== 0) {
                Category::query()->whereKey(array_slice($this->categoriesWithDescendantIds([$category->id]), 1))->increment('depth', $shift);
            }

            return $category->update($data);
        });

        return $updated;
    }

    /**
     * @return array<string, mixed>
     */
    public function imageField(int|string $id): array
    {
        return $this->getCategoryById($id)->mediaField('category.media.image', 'regular');
    }

    public function imageUrl(int|string $id, string $conversion): ?string
    {
        return $this->getCategoryById($id)->mediaUrl('category.media.image', $conversion);
    }

    public function updateImage(int|string $id, ?UploadedFile $image = null, bool $remove = false): void
    {
        $this->getCategoryById($id)->syncMedia('category.media.image', $image, $remove);
    }

    public function delete(int|string $id): bool
    {
        $category = $this->getCategoryById($id);

        if ($category->children()->exists()) {
            throw ValidationException::withMessages(['category' => __('Delete or move the sub-categories first.')]);
        }

        $deleted = (bool) DB::transaction(function () use ($category): bool {
            Product::query()->where('category_id', $category->id)->update(['category_id' => null]);

            return (bool) $category->delete();
        });

        return $deleted;
    }

    private function depthUnder(?Category $category, mixed $parentId): int
    {
        if (blank($parentId)) {
            return 1;
        }

        $parent = Category::find((int) $parentId);

        if ($parent === null || $parent->depth >= categoryLevel()) {
            throw ValidationException::withMessages(['parentLevels.0' => __('Choose a parent up to level :level.', ['level' => categoryLevel() - 1])]);
        }

        if ($category === null) {
            return $parent->depth + 1;
        }

        $subtree = $this->categoriesWithDescendantIds([$category->id]);

        if (in_array($parent->id, $subtree, true)) {
            throw ValidationException::withMessages(['parentLevels.0' => __('A category cannot be moved under itself or its sub-categories.')]);
        }

        $height = (int) Category::query()->whereKey($subtree)->max('depth') - $category->depth;

        if ($parent->depth + 1 + $height > categoryLevel()) {
            throw ValidationException::withMessages(['parentLevels.0' => __('Moving it here would make its sub-categories deeper than :level levels.', ['level' => categoryLevel()])]);
        }

        return $parent->depth + 1;
    }

    /**
     * @param  Builder<Category>  $query
     * @return array<int, string>
     */
    private function labelled(Builder $query): array
    {
        return $query->with('parent:id,name')
            ->get(['id', 'name', 'parent_id'])
            ->mapWithKeys(fn (Category $category): array => [$category->id => $category->parent ? $category->parent->name.' › '.$category->name : $category->name])
            ->all();
    }
}
