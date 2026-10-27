<?php

namespace App\Services\Backoffice\Catalog;

use App\Enums\Catalog\ProductBadgeEnum;
use App\Enums\Catalog\ProductStatusEnum;
use App\Models\Product;
use App\Traits\AiAssistant\AiAssistantEmbeddingTrait;
use App\Traits\Query\CategoryTreeTrait;
use App\Traits\Query\TableQueryTrait;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ProductService
{
    use AiAssistantEmbeddingTrait, CategoryTreeTrait, TableQueryTrait;

    private const IMAGES = 'product.media.images';

    /**
     * @return list<string>
     */
    public function sortableColumns(): array
    {
        return [
            'id',
            'sku',
            'name',
            'price',
            'quantity',
            'status',
            'badge',
            'created_at',
        ];
    }

    /**
     * @return LengthAwarePaginator<int, Product>
     */
    public function getProducts(
        string $search = '',
        int $perPage = 10,
        ?ProductStatusEnum $status = null,
        ?int $categoryId = null,
        string $sortBy = 'created_at',
        string $sortDirection = 'desc',
    ): LengthAwarePaginator {
        return $this->paginateTableQuery(
            query: Product::query()
                ->with(['media', 'category'])
                ->when($categoryId, fn (Builder $query, int $id): Builder => $query->whereIn('category_id', $this->categoriesWithDescendantIds([$id], categoryLevel()))),
            search: $search,
            searchColumns: ['name', 'sku'],
            filters: [
                'status' => $status,
            ],
            perPage: $perPage,
            sortBy: $sortBy,
            sortDirection: $sortDirection,
            sortableColumns: $this->sortableColumns(),
        );
    }

    public function getProductById(int|string $id): Product
    {
        return Product::query()->with(['category.parent', 'media'])->findOrFail($id);
    }

    /**
     * @param  list<int>  $removeIds
     * @param  list<string>  $order
     * @param  list<UploadedFile>  $uploads
     * @return array{image: bool, accept: string, hint: string, max: int, count: int, items: list<array{key: string, id: int|null, upload_index: int|null, name: string, url: string|null, is_new: bool, is_thumbnail: bool, is_removed: bool}>}
     */
    public function imageGallery(int|string $id, array $removeIds = [], array $order = [], array $uploads = []): array
    {
        $product = $this->getProductById($id);
        $items = $product->configuredMediaItems(self::IMAGES)->values()->toBase()
            ->map(fn (Media $item): array => [
                'key' => 'media-'.$item->id,
                'id' => $item->id,
                'upload_index' => null,
                'name' => $item->file_name,
                'url' => $product->mediaItemUrl($item, 'regular'),
                'is_new' => false,
                'is_removed' => in_array($item->id, $removeIds, true),
            ])
            ->concat(collect($uploads)->map(fn (UploadedFile $file, int $index): array => [
                'key' => 'upload-'.$index,
                'id' => null,
                'upload_index' => $index,
                'name' => $file->getClientOriginalName(),
                'url' => $file instanceof TemporaryUploadedFile && $file->isPreviewable() ? $file->temporaryUrl() : null,
                'is_new' => true,
                'is_removed' => false,
            ]))
            ->keyBy('key');

        $items = $items->only($order)
            ->sortBy(fn (array $item): int|false => array_search($item['key'], $order, true))
            ->concat($items->except($order))
            ->values();
        $thumbnailKey = $items->firstWhere('is_removed', false)['key'] ?? null;

        return [
            'image' => true,
            'accept' => mediaAccept(self::IMAGES),
            'hint' => mediaUploadHint(self::IMAGES),
            'max' => mediaMaxFiles(self::IMAGES),
            'count' => $items->where('is_removed', false)->count(),
            'items' => array_values($items
                ->map(fn (array $item): array => [...$item, 'is_thumbnail' => $item['key'] === $thumbnailKey])
                ->all()),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Product
    {
        $product = DB::transaction(fn (): Product => Product::create($this->productData($data)));

        $this->syncAiAssistantEmbeddingLater($product);

        return $product;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int|string $id, array $data): bool
    {
        $product = $this->getProductById($id);
        $updated = DB::transaction(fn (): bool => $product->update($this->productData($data)));

        $this->syncAiAssistantEmbeddingLater($product);

        return $updated;
    }

    public function delete(int|string $id): bool
    {
        return (bool) Product::findOrFail($id)->delete();
    }

    /**
     * @param  list<UploadedFile>  $images
     * @param  list<int>  $removeIds
     * @param  list<string>  $order
     */
    public function updateImages(int|string $id, array $images = [], array $removeIds = [], array $order = []): void
    {
        $product = Product::query()->with('media')->findOrFail($id);
        $keptCount = $product->configuredMediaItems(self::IMAGES)->whereNotIn('id', $removeIds)->count();

        if ($keptCount + count($images) > mediaMaxFiles(self::IMAGES)) {
            throw ValidationException::withMessages([
                'imageUploads' => __('A product can have at most :max images.', ['max' => mediaMaxFiles(self::IMAGES)]),
            ]);
        }

        foreach ($removeIds as $mediaId) {
            $product->removeMediaItem(self::IMAGES, $mediaId);
        }

        $uploadedMediaIds = [];

        foreach ($images as $index => $image) {
            $uploadedMediaIds['upload-'.$index] = $product->attachMedia(self::IMAGES, $image)->id;
        }

        $product->load('media')->setMediaItemsOrder(self::IMAGES, array_values(array_filter(array_map(
            fn (string $key): ?int => $uploadedMediaIds[$key] ?? (str_starts_with($key, 'media-') ? (int) substr($key, 6) : null),
            $order,
        ))));
    }

    public function setThumbnail(int|string $id, int $mediaId): void
    {
        Product::query()->with('media')->findOrFail($id)->setMediaItemsOrder(self::IMAGES, [$mediaId]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function productData(array $data): array
    {
        $quantity = (int) ($data['quantity'] ?? 0);
        $status = ProductStatusEnum::from($data['status']);

        return [
            'category_id' => filled($data['category_id'] ?? null) ? (int) $data['category_id'] : null,
            'sku' => trim($data['sku']),
            'name' => $data['name'],
            'description' => sanitizeRichText($data['description'] ?? null),
            'price' => (int) ($data['price'] ?? 0),
            'discount_percentage' => filled($data['discount_percentage'] ?? null) ? (int) $data['discount_percentage'] : null,
            'quantity' => $quantity,
            'status' => match (true) {
                ! in_array($status, ProductStatusEnum::listed(), true) => $status,
                $quantity > 0 => ProductStatusEnum::InStock,
                default => ProductStatusEnum::OutOfStock,
            },
            'badge' => ProductBadgeEnum::tryFrom((string) ($data['badge'] ?? '')),
            'is_featured' => (bool) ($data['is_featured'] ?? false),
        ];
    }
}
