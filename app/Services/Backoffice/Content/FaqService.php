<?php

namespace App\Services\Backoffice\Content;

use App\Enums\Content\FaqStatusEnum;
use App\Models\Faq;
use App\Traits\Query\TableQueryTrait;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class FaqService
{
    use TableQueryTrait;

    /**
     * @return list<string>
     */
    public function sortableColumns(): array
    {
        return ['id', 'title', 'status', 'created_at'];
    }

    /**
     * @return LengthAwarePaginator<int, Faq>
     */
    public function getFaqs(
        string $search = '',
        int $perPage = 10,
        ?FaqStatusEnum $status = null,
        string $sortBy = 'created_at',
        string $sortDirection = 'desc',
    ): LengthAwarePaginator {
        return $this->paginateTableQuery(
            query: Faq::query(),
            search: $search,
            searchColumns: ['title', 'description'],
            filters: ['status' => $status],
            perPage: $perPage,
            sortBy: $sortBy,
            sortDirection: $sortDirection,
            sortableColumns: $this->sortableColumns(),
        );
    }

    public function getFaqById(int|string $id): Faq
    {
        return Faq::findOrFail($id);
    }

    /**
     * @param  array{title: string, description: string, status: FaqStatusEnum}  $data
     */
    public function create(array $data): Faq
    {
        $faq = Faq::create($data);

        return $faq;
    }

    /**
     * @param  array{title: string, description: string, status: FaqStatusEnum}  $data
     */
    public function update(int|string $id, array $data): bool
    {
        $updated = $this->getFaqById($id)->update($data);

        return $updated;
    }

    public function delete(int|string $id): bool
    {
        $deleted = (bool) $this->getFaqById($id)->delete();

        return $deleted;
    }
}
