<?php

namespace App\Services\Backoffice\Content;

use App\Enums\Content\BannerStatusEnum;
use App\Models\Banner;
use App\Traits\Query\TableQueryTrait;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;

class BannerService
{
    use TableQueryTrait;

    /**
     * @return list<string>
     */
    public function sortableColumns(): array
    {
        return ['id', 'title', 'sort_order', 'status', 'starts_at', 'ends_at', 'created_at'];
    }

    /**
     * @return LengthAwarePaginator<int, Banner>
     */
    public function getBanners(string $search = '', int $perPage = 10, ?BannerStatusEnum $status = null, string $sortBy = 'sort_order', string $sortDirection = 'asc'): LengthAwarePaginator
    {
        return $this->paginateTableQuery(
            query: Banner::query()->with('media'),
            search: $search,
            searchColumns: ['title', 'subtitle'],
            filters: ['status' => $status],
            perPage: $perPage,
            sortBy: $sortBy,
            sortDirection: $sortDirection,
            sortableColumns: $this->sortableColumns(),
        );
    }

    public function getBannerById(int|string $id): Banner
    {
        return Banner::query()->with('media')->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?UploadedFile $image = null): Banner
    {
        $banner = Banner::create($this->prepareData($data));
        $banner->syncMedia('banner.media.image', $image);

        return $banner;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int|string $id, array $data, ?UploadedFile $image = null): bool
    {
        $banner = $this->getBannerById($id);
        $updated = $banner->update($this->prepareData($data));
        $banner->syncMedia('banner.media.image', $image);

        return $updated;
    }

    public function delete(int|string $id): bool
    {
        $deleted = (bool) $this->getBannerById($id)->delete();

        return $deleted;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function prepareData(array $data): array
    {
        return [
            'title' => $data['title'],
            'subtitle' => ($data['subtitle'] ?? null) ?: null,
            'show_text' => (bool) ($data['show_text'] ?? true),
            'link' => ($data['link'] ?? null) ?: null,
            'sort_order' => (int) ($data['sort_order'] ?? 1),
            'status' => $data['status'],
            'starts_at' => ($data['starts_at'] ?? null) ?: null,
            'ends_at' => ($data['ends_at'] ?? null) ?: null,
        ];
    }
}
