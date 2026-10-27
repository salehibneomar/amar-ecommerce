<?php

namespace App\Livewire\Backoffice\Categories;

use App\Enums\Catalog\CategoryStatusEnum;
use App\Livewire\Concerns\InteractsWithCategoryLevels;
use App\Livewire\Concerns\InteractsWithSeoMeta;
use App\Livewire\Concerns\InteractsWithTable;
use App\Models\Category;
use App\Services\Backoffice\Catalog\CategoryService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

/**
 * @property-read list<array{options: array<int, string>, has_more: bool, disabled: bool}> $parentLevelOptions
 * @property-read string|null $imageUrl
 */
class Index extends Component
{
    use InteractsWithCategoryLevels, InteractsWithSeoMeta, InteractsWithTable, WithFileUploads, WithPagination;

    public string $status = '';

    public bool $rootOnly = false;

    public bool $featuredOnly = false;

    public bool $showForm = false;

    public bool $showDeleteConfirmation = false;

    public ?int $editingCategoryId = null;

    public bool $showDetails = false;

    /** @var array<string, string|bool|null> */
    public array $details = [];

    public ?int $deletingCategoryId = null;

    public string $name = '';

    public string $description = '';

    /** @var list<string> */
    public array $parentLevels = [];

    /** @var array<int, int> */
    public array $parentLevelsLimits = [];

    /** @var list<string> */
    public array $categoryFilter = [];

    /** @var array<int, int> */
    public array $categoryFilterLimits = [];

    public string $categoryStatus = 'draft';

    public string $sortOrder = '1';

    public bool $isFeatured = false;

    public bool $showMediaPreview = false;

    public ?int $previewCategoryId = null;

    public bool $showImageViewer = false;

    public bool $showMediaUpload = false;

    public ?int $mediaCategoryId = null;

    public ?TemporaryUploadedFile $imageUpload = null;

    public bool $removeImage = false;

    protected CategoryService $categoryService;

    public function mount(): void
    {
        $this->sortBy = 'sort_order';
        $this->sortDirection = 'asc';
    }

    public function boot(CategoryService $categoryService): void
    {
        $this->categoryService = $categoryService;
    }

    protected function sortableColumns(): array
    {
        return $this->categoryService->sortableColumns();
    }

    public function updatedStatus(string $status): void
    {
        $this->status = CategoryStatusEnum::tryFrom($status)->value ?? '';
        $this->resetTableFilters();
    }

    public function updatedRootOnly(): void
    {
        if ($this->rootOnly) {
            $this->reset(['categoryFilter', 'categoryFilterLimits']);
        }

        $this->resetTableFilters();
    }

    public function updatedFeaturedOnly(): void
    {
        $this->resetTableFilters();
    }

    public function updatedShowForm(bool $showForm): void
    {
        if (! $showForm) {
            $this->resetForm();
        }
    }

    public function createCategory(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function viewCategory(int $categoryId): void
    {
        $category = $this->categoryService->getCategoryById($categoryId);

        $this->details = [
            __('Name') => $category->name,
            __('Slug') => $category->slug,
            __('Parent') => $category->parent?->name,
            __('Status') => $category->status->label(),
            __('Display Order') => (string) $category->sort_order,
            __('Featured') => $category->is_featured,
            __('Created') => formatDateTime($category->created_at),
            __('Last Updated by') => $category->lastUpdateLabel(),
            __('Description') => $category->description,
        ];
        $this->showDetails = true;
    }

    public function editCategory(int $categoryId): void
    {
        $category = $this->categoryService->getCategoryById($categoryId);

        $this->editingCategoryId = $category->id;
        $this->name = $category->name;
        $this->description = $category->description ?? '';
        $this->setCategoryLevels('parentLevels', $this->categoryService->getCategoryLevelIds($category->parent_id));
        $this->categoryStatus = $category->status->value;
        $this->sortOrder = (string) $category->sort_order;
        $this->isFeatured = $category->is_featured;
        $this->showForm = true;
    }

    public function saveCategory(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'parentLevels' => ['array'],
            'parentLevels.*' => ['nullable', 'integer', Rule::exists('categories', 'id')->withoutTrashed()],
            'categoryStatus' => ['required', Rule::enum(CategoryStatusEnum::class)],
            'sortOrder' => ['required', 'integer', 'min:1', 'max:9999'],
            'isFeatured' => ['boolean'],
        ]);

        $data = [
            'name' => $validated['name'],
            'description' => $validated['description'] ?: null,
            'parent_id' => $this->selectedCategoryLevelId('parentLevels'),
            'status' => CategoryStatusEnum::from($validated['categoryStatus']),
            'sort_order' => (int) $validated['sortOrder'],
            'is_featured' => $validated['isFeatured'],
        ];

        if ($this->editingCategoryId) {
            $this->categoryService->update($this->editingCategoryId, $data);
            $message = __('Category updated successfully.');
        } else {
            $this->categoryService->create($data);
            $message = __('Category created successfully.');
        }

        $this->showForm = false;
        $this->resetForm();
        unset($this->categories, $this->parentLevelOptions);
        $this->dispatch('toast-show', slots: ['text' => $message], dataset: ['variant' => 'success']);
    }

    public function updatedShowMediaPreview(bool $showMediaPreview): void
    {
        if (! $showMediaPreview) {
            $this->reset(['previewCategoryId', 'showImageViewer']);
        }
    }

    public function updatedShowMediaUpload(bool $showMediaUpload): void
    {
        if (! $showMediaUpload) {
            $this->resetMediaUpload();
        }
    }

    public function viewMedia(int $categoryId): void
    {
        $this->previewCategoryId = $this->categoryService->getCategoryById($categoryId)->id;
        unset($this->previewField, $this->imageUrl);
        $this->showMediaPreview = true;
    }

    public function viewImage(): void
    {
        if (! $this->imageUrl) {
            throw ValidationException::withMessages([
                'imageUpload' => __('This category does not have an image.'),
            ]);
        }

        $this->showImageViewer = true;
    }

    public function uploadMedia(int $categoryId): void
    {
        $this->resetMediaUpload();
        $this->mediaCategoryId = $categoryId;
        unset($this->uploadField);
        $this->showMediaUpload = true;
    }

    public function saveMedia(): void
    {
        if (! $this->mediaCategoryId) {
            throw ValidationException::withMessages([
                'mediaCategoryId' => __('Select a category before uploading media.'),
            ]);
        }

        $this->validate([
            'imageUpload' => mediaValidationRules('category.media.image'),
        ]);

        $this->categoryService->updateImage($this->mediaCategoryId, $this->imageUpload, $this->removeImage);

        $this->showMediaUpload = false;
        $this->resetMediaUpload();
        unset($this->categories);
        $this->dispatch('toast-show', slots: ['text' => __('Category media updated successfully.')], dataset: ['variant' => 'success']);
    }

    public function confirmDeleteCategory(int $categoryId): void
    {
        $this->deletingCategoryId = $categoryId;
        $this->showDeleteConfirmation = true;
    }

    public function deleteCategory(): void
    {
        if (! $this->deletingCategoryId) {
            throw ValidationException::withMessages([
                'deletingCategoryId' => __('Select a category before deleting it.'),
            ]);
        }

        try {
            $this->categoryService->delete($this->deletingCategoryId);
        } catch (ValidationException $exception) {
            $this->showDeleteConfirmation = false;
            $this->dispatch('toast-show', slots: ['text' => $exception->validator->errors()->first()], dataset: ['variant' => 'danger']);

            return;
        }

        $this->showDeleteConfirmation = false;
        $this->deletingCategoryId = null;
        unset($this->categories, $this->parentLevelOptions);
        $this->dispatch('toast-show', slots: ['text' => __('Category deleted successfully.')], dataset: ['variant' => 'success']);
    }

    /**
     * @return LengthAwarePaginator<int, Category>
     */
    #[Computed]
    public function categories(): LengthAwarePaginator
    {
        return $this->categoryService->getCategories(
            search: $this->search,
            perPage: $this->perPage,
            status: CategoryStatusEnum::tryFrom($this->status),
            parentId: $this->selectedCategoryLevelId('categoryFilter'),
            rootOnly: $this->rootOnly,
            featuredOnly: $this->featuredOnly,
            sortBy: $this->sortBy,
            sortDirection: $this->sortDirection,
        );
    }

    /**
     * @return list<array{options: array<int, string>, has_more: bool, disabled: bool}>
     */
    #[Computed]
    public function categoryFilterOptions(): array
    {
        return $this->levelOptionsFor('categoryFilter', categoryLevel() - 1);
    }

    /**
     * @return list<array{options: array<int, string>, has_more: bool, disabled: bool}>
     */
    #[Computed]
    public function parentLevelOptions(): array
    {
        return array_map(function (array $batch): array {
            unset($batch['options'][$this->editingCategoryId]);

            return $batch;
        }, $this->levelOptionsFor('parentLevels', categoryLevel() - 1));
    }

    /**
     * @return array<string, int>
     */
    protected function categoryLevelProperties(): array
    {
        return ['categoryFilter' => 5, 'parentLevels' => 10];
    }

    protected function categoryLevelsUpdated(string $property): void
    {
        if ($property === 'categoryFilter') {
            $this->rootOnly = false;
            $this->resetTableFilters();
        }
    }

    /**
     * @return array<string, mixed>
     */
    #[Computed]
    public function uploadField(): array
    {
        return $this->mediaCategoryId ? $this->categoryService->imageField($this->mediaCategoryId) : [];
    }

    /**
     * @return array<string, mixed>
     */
    #[Computed]
    public function previewField(): array
    {
        return $this->previewCategoryId ? $this->categoryService->imageField($this->previewCategoryId) : [];
    }

    #[Computed]
    public function imageUrl(): ?string
    {
        return $this->previewCategoryId ? $this->categoryService->imageUrl($this->previewCategoryId, 'large') : null;
    }

    /**
     * @return array<string, string>
     */
    #[Computed]
    public function statusOptions(): array
    {
        return collect(CategoryStatusEnum::cases())
            ->mapWithKeys(fn (CategoryStatusEnum $status): array => [$status->value => $status->label()])
            ->all();
    }

    protected function seoMetaOwnerType(): string
    {
        return Category::class;
    }

    public function render(): View
    {
        return view('components.backoffice.categories.index');
    }

    private function resetForm(): void
    {
        $this->reset(['editingCategoryId', 'name', 'description', 'parentLevels', 'parentLevelsLimits', 'isFeatured']);
        $this->categoryStatus = CategoryStatusEnum::Draft->value;
        $this->sortOrder = '1';
        $this->resetValidation();
    }

    private function resetMediaUpload(): void
    {
        $this->reset(['mediaCategoryId', 'imageUpload', 'removeImage']);
        $this->resetValidation();
    }
}
