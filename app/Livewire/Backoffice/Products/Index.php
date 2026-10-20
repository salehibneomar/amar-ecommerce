<?php

namespace App\Livewire\Backoffice\Products;

use App\Enums\Catalog\ProductBadgeEnum;
use App\Enums\Catalog\ProductStatusEnum;
use App\Livewire\Concerns\InteractsWithCategoryLevels;
use App\Livewire\Concerns\InteractsWithTable;
use App\Models\Product;
use App\Services\Backoffice\Catalog\CategoryService;
use App\Services\Backoffice\Catalog\ProductService;
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
 * @property-read Product|null $viewingProduct
 */
class Index extends Component
{
    use InteractsWithCategoryLevels, InteractsWithTable, WithFileUploads, WithPagination;

    public string $status = '';

    public bool $showForm = false;

    public bool $showDeleteConfirmation = false;

    public bool $showDetails = false;

    public ?int $editingProductId = null;

    public ?int $deletingProductId = null;

    public ?int $viewingProductId = null;

    public bool $showMediaPreview = false;

    public ?int $previewProductId = null;

    public bool $showMediaUpload = false;

    public ?int $mediaProductId = null;

    /** @var list<TemporaryUploadedFile> */
    public array $imageUploads = [];

    /** @var list<int> */
    public array $removeImageIds = [];

    /** @var list<string> */
    public array $imageOrder = [];

    public string $sku = '';

    public string $name = '';

    public string $price = '';

    public string $discountPercentage = '';

    /** @var list<string> */
    public array $categoryLevels = [];

    /** @var array<int, int> */
    public array $categoryLevelsLimits = [];

    /** @var list<string> */
    public array $categoryFilter = [];

    /** @var array<int, int> */
    public array $categoryFilterLimits = [];

    public string $description = '';

    public string $quantity = '';

    public string $productStatus = 'archived';

    public string $badge = '';

    public bool $isFeatured = false;

    protected ProductService $productService;

    protected CategoryService $categoryService;

    public function mount(): void
    {
        $this->sortBy = 'name';
        $this->sortDirection = 'asc';
    }

    public function boot(ProductService $productService, CategoryService $categoryService): void
    {
        $this->productService = $productService;
        $this->categoryService = $categoryService;
    }

    protected function sortableColumns(): array
    {
        return $this->productService->sortableColumns();
    }

    public function updatedStatus(string $status): void
    {
        $this->status = ProductStatusEnum::tryFrom($status)->value ?? '';
        $this->resetTableFilters();
    }

    public function updatedShowForm(bool $showForm): void
    {
        if (! $showForm) {
            $this->resetForm();
        }
    }

    public function updatedShowDetails(bool $showDetails): void
    {
        if (! $showDetails) {
            $this->viewingProductId = null;
        }
    }

    public function updatedShowMediaPreview(bool $showMediaPreview): void
    {
        if (! $showMediaPreview) {
            $this->reset('previewProductId');
        }
    }

    public function updatedShowMediaUpload(bool $showMediaUpload): void
    {
        if (! $showMediaUpload) {
            $this->resetMediaUpload();
        }
    }

    public function createProduct(): void
    {
        $this->resetForm();
        $this->showDetails = false;
        $this->viewingProductId = null;
        $this->showForm = true;
    }

    public function viewProduct(int $productId): void
    {
        $this->viewingProductId = $productId;
        $this->showDetails = true;
    }

    public function viewMedia(int $productId): void
    {
        $this->previewProductId = $productId;
        unset($this->previewProduct);
        $this->showMediaPreview = true;
    }

    public function uploadMedia(int $productId): void
    {
        $this->resetMediaUpload();
        $this->mediaProductId = $productId;
        unset($this->mediaProduct, $this->uploadGallery);
        $this->showMediaUpload = true;
    }

    public function updatedImageUploads(): void
    {
        $this->imageOrder = array_values(array_filter($this->imageOrder, fn (string $key): bool => ! str_starts_with($key, 'upload-')));
    }

    public function toggleRemoveImage(int $mediaId): void
    {
        $this->removeImageIds = in_array($mediaId, $this->removeImageIds, true)
            ? array_values(array_diff($this->removeImageIds, [$mediaId]))
            : [...$this->removeImageIds, $mediaId];
        unset($this->uploadGallery);
    }

    public function chooseThumbnail(string $key): void
    {
        $keys = array_column($this->uploadGallery['items'] ?? [], 'key');

        if (! in_array($key, $keys, true)) {
            return;
        }

        $this->imageOrder = [$key, ...array_values(array_diff($keys, [$key]))];
        $this->removeImageIds = array_values(array_diff($this->removeImageIds, [(int) str_replace('media-', '', $key)]));
        unset($this->uploadGallery);
    }

    public function moveImage(string $key, int $offset): void
    {
        $keys = array_column($this->uploadGallery['items'] ?? [], 'key');
        $from = array_search($key, $keys, true);

        if ($from === false || ! isset($keys[$to = $from + ($offset < 0 ? -1 : 1)])) {
            return;
        }

        [$keys[$from], $keys[$to]] = [$keys[$to], $keys[$from]];
        $this->imageOrder = array_values($keys);
        unset($this->uploadGallery);
    }

    public function discardImageUpload(int $index): void
    {
        $this->imageUploads = array_values(array_filter($this->imageUploads, fn (int $key): bool => $key !== $index, ARRAY_FILTER_USE_KEY));
        $this->imageOrder = array_values(array_filter(array_map(
            fn (string $key): ?string => match (true) {
                $key === 'upload-'.$index => null,
                str_starts_with($key, 'upload-') && (int) substr($key, 7) > $index => 'upload-'.((int) substr($key, 7) - 1),
                default => $key,
            },
            $this->imageOrder,
        )));
        unset($this->uploadGallery);
    }

    public function saveMedia(): void
    {
        if (! $this->mediaProductId) {
            throw ValidationException::withMessages([
                'mediaProductId' => __('Select a product before uploading images.'),
            ]);
        }

        $this->validate([
            'imageUploads' => ['array', 'max:'.mediaMaxFiles('product.media.images')],
            'imageUploads.*' => mediaValidationRules('product.media.images', required: true),
            'removeImageIds' => ['array'],
            'removeImageIds.*' => ['integer'],
            'imageOrder' => ['array'],
            'imageOrder.*' => ['string'],
        ]);

        $this->productService->updateImages(
            $this->mediaProductId,
            $this->imageUploads,
            $this->removeImageIds,
            $this->imageOrder,
        );

        $this->showMediaUpload = false;
        $this->resetMediaUpload();
        unset($this->products, $this->mediaProduct);

        $this->dispatch(
            'toast-show',
            slots: ['text' => __('Product images updated successfully.')],
            dataset: ['variant' => 'success'],
        );
    }

    public function setPreviewThumbnail(int $mediaId): void
    {
        if (! $this->previewProductId) {
            return;
        }

        $this->productService->setThumbnail($this->previewProductId, $mediaId);
        unset($this->previewProduct, $this->products);

        $this->dispatch(
            'toast-show',
            slots: ['text' => __('Thumbnail updated successfully.')],
            dataset: ['variant' => 'success'],
        );
    }

    public function editProduct(int $productId): void
    {
        $this->showDetails = false;
        $this->viewingProductId = null;

        $product = $this->productService->getProductById($productId);

        $this->editingProductId = $product->id;
        $this->sku = $product->sku;
        $this->name = $product->name;
        $this->price = (string) $product->price;
        $this->discountPercentage = $product->discount_percentage !== null ? (string) $product->discount_percentage : '';
        $this->setCategoryLevels('categoryLevels', $this->categoryService->getCategoryLevelIds($product->category_id));
        $this->description = $product->description ?? '';
        $this->quantity = (string) $product->quantity;
        $this->productStatus = $product->status->value;
        $this->badge = $product->badge->value ?? '';
        $this->isFeatured = $product->is_featured;
        $this->showForm = true;
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return ['categoryLevels.0' => __('category'), 'categoryLevels.*' => __('category')];
    }

    public function saveProduct(): void
    {
        $validated = $this->validate([
            'sku' => ['required', 'string', 'max:64', 'regex:/^[A-Za-z0-9._-]+$/', Rule::unique('products', 'sku')->ignore($this->editingProductId)],
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'integer', 'min:0', 'max:99999999'],
            'discountPercentage' => ['nullable', 'integer', 'min:0', 'max:100'],
            'categoryLevels' => ['array'],
            'categoryLevels.0' => ['required'],
            'categoryLevels.*' => ['nullable', 'integer', Rule::exists('categories', 'id')->where(fn ($query) => $query->where('depth', '<=', categoryLevel()))->withoutTrashed()],
            'description' => ['nullable', 'string'],
            'quantity' => ['required', 'integer', 'min:0', 'max:99999999'],
            'productStatus' => ['required', Rule::enum(ProductStatusEnum::class)],
            'badge' => ['nullable', Rule::enum(ProductBadgeEnum::class)],
            'isFeatured' => ['boolean'],
        ]);

        $data = [
            'sku' => $validated['sku'],
            'name' => $validated['name'],
            'price' => $validated['price'],
            'discount_percentage' => $validated['discountPercentage'],
            'category_id' => $this->selectedCategoryLevelId('categoryLevels'),
            'description' => $validated['description'],
            'quantity' => $validated['quantity'],
            'status' => $validated['productStatus'],
            'badge' => $validated['badge'],
            'is_featured' => $validated['isFeatured'],
        ];

        $createdProductId = null;

        if ($this->editingProductId) {
            $this->productService->update($this->editingProductId, $data);
            $message = __('Product updated successfully.');
        } else {
            $createdProductId = $this->productService->create($data)->id;
            $message = __('Product created successfully. Upload the images next.');
        }

        $this->showForm = false;
        $this->resetForm();
        unset($this->products);

        $this->dispatch(
            'toast-show',
            slots: ['text' => $message],
            dataset: ['variant' => 'success'],
        );

        if ($createdProductId) {
            $this->uploadMedia($createdProductId);
        }
    }

    public function confirmDeleteProduct(int $productId): void
    {
        $this->deletingProductId = $productId;
        $this->showDeleteConfirmation = true;
    }

    public function deleteProduct(): void
    {
        if (! $this->deletingProductId) {
            throw ValidationException::withMessages([
                'deletingProductId' => __('Select a product before deleting it.'),
            ]);
        }

        $this->productService->delete($this->deletingProductId);

        $this->showDeleteConfirmation = false;
        $this->deletingProductId = null;
        unset($this->products);

        $this->dispatch(
            'toast-show',
            slots: ['text' => __('Product deleted successfully.')],
            dataset: ['variant' => 'success'],
        );
    }

    /**
     * @return LengthAwarePaginator<int, Product>
     */
    #[Computed]
    public function products(): LengthAwarePaginator
    {
        return $this->productService->getProducts(
            search: $this->search,
            perPage: $this->perPage,
            status: ProductStatusEnum::tryFrom($this->status),
            categoryId: $this->selectedCategoryLevelId('categoryFilter'),
            sortBy: $this->sortBy,
            sortDirection: $this->sortDirection,
        );
    }

    #[Computed]
    public function viewingProduct(): ?Product
    {
        return $this->viewingProductId
            ? $this->productService->getProductById($this->viewingProductId)
            : null;
    }

    /**
     * @return list<array{options: array<int, string>, has_more: bool, disabled: bool}>
     */
    #[Computed]
    public function categoryFilterOptions(): array
    {
        return $this->levelOptionsFor('categoryFilter', categoryLevel());
    }

    /**
     * @return list<array{options: array<int, string>, has_more: bool, disabled: bool}>
     */
    #[Computed]
    public function categoryLevelOptions(): array
    {
        return $this->levelOptionsFor('categoryLevels', categoryLevel());
    }

    /**
     * @return array<string, int>
     */
    protected function categoryLevelProperties(): array
    {
        return ['categoryFilter' => 5, 'categoryLevels' => 10];
    }

    protected function categoryLevelsUpdated(string $property): void
    {
        if ($property === 'categoryFilter') {
            $this->resetTableFilters();
        }
    }

    #[Computed]
    public function viewingProductCategory(): ?string
    {
        return $this->categoryService->getCategoryLabel($this->viewingProduct?->category_id);
    }

    /**
     * @return array<string, string>
     */
    #[Computed]
    public function statusOptions(): array
    {
        return collect(ProductStatusEnum::cases())
            ->mapWithKeys(
                fn (ProductStatusEnum $status) => [
                    $status->value => $status->label(),
                ],
            )
            ->all();
    }

    /**
     * @return array<string, string>
     */
    #[Computed]
    public function badgeOptions(): array
    {
        return collect(ProductBadgeEnum::cases())
            ->mapWithKeys(
                fn (ProductBadgeEnum $badge) => [
                    $badge->value => $badge->label(),
                ],
            )
            ->all();
    }

    /**
     * @return array{image: bool, accept: string, hint: string, max: int, count: int, items: list<array{key: string, id: int|null, upload_index: int|null, name: string, url: string|null, is_new: bool, is_thumbnail: bool, is_removed: bool}>}|null
     */
    #[Computed]
    public function uploadGallery(): ?array
    {
        return $this->mediaProductId
            ? $this->productService->imageGallery($this->mediaProductId, $this->removeImageIds, $this->imageOrder, $this->imageUploads)
            : null;
    }

    public function render(): View
    {
        return view('components.backoffice.products.index');
    }

    private function resetForm(): void
    {
        $this->reset([
            'editingProductId',
            'sku',
            'name',
            'price',
            'discountPercentage',
            'categoryLevels',
            'categoryLevelsLimits',
            'description',
            'quantity',
            'badge',
            'isFeatured',
        ]);

        $this->productStatus = ProductStatusEnum::Archived->value;
        $this->resetValidation();
    }

    #[Computed]
    public function previewProduct(): ?Product
    {
        return $this->previewProductId
            ? $this->productService->getProductById($this->previewProductId)
            : null;
    }

    #[Computed]
    public function mediaProduct(): ?Product
    {
        return $this->mediaProductId
            ? $this->productService->getProductById($this->mediaProductId)
            : null;
    }

    private function resetMediaUpload(): void
    {
        $this->reset([
            'mediaProductId',
            'imageUploads',
            'removeImageIds',
            'imageOrder',
        ]);

        $this->resetValidation();
    }
}
