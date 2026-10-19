<?php

namespace App\Services\Frontend\Account;

use App\Enums\Catalog\ProductStatusEnum;
use App\Enums\Order\PaymentStatusEnum;
use App\Models\Book;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class BookLibraryService
{
    private const RECENT_BOOKS_LIMIT = 4;

    private const LIBRARY_PER_PAGE = 12;

    public function recordOpen(User $user, int $productId): void
    {
        $user->openedBooks()->syncWithoutDetaching([$productId => ['opened_at' => now()]]);
    }

    public function countOpenedBooks(User $user): int
    {
        return $user->openedBooks()->count();
    }

    /**
     * @return Collection<int, Book>
     */
    public function getRecentBooks(User $user): Collection
    {
        return $user->openedBooks()
            ->select('products.*', 'book_reads.opened_at as last_opened_at')
            ->withCasts(['last_opened_at' => 'datetime'])
            ->with('media')
            ->where('status', ProductStatusEnum::InStock)
            ->orderByPivot('opened_at', 'desc')
            ->limit(self::RECENT_BOOKS_LIMIT)
            ->get();
    }

    public function ownsProduct(?User $user, int|string $productId): bool
    {
        return $user !== null && $this->purchasedItems($user)->where('product_id', $productId)->exists();
    }

    /**
     * @return list<int>
     */
    public function ownedProductIds(?User $user): array
    {
        if ($user === null) {
            return [];
        }

        return array_values($this->purchasedItems($user)->distinct()->pluck('product_id')->map(fn (mixed $id): int => (int) $id)->all());
    }

    /**
     * @return LengthAwarePaginator<int, Book>
     */
    public function getLibrary(User $user): LengthAwarePaginator
    {
        return Book::query()
            ->with('media')
            ->whereIn('id', $this->purchasedItems($user)->select('product_id'))
            ->latest()
            ->paginate(self::LIBRARY_PER_PAGE);
    }

    /**
     * @return Builder<OrderItem>
     */
    private function purchasedItems(User $user): Builder
    {
        return OrderItem::query()
            ->whereNotNull('product_id')
            ->whereHas('order', fn (Builder $query): Builder => $query
                ->where('user_id', $user->id)
                ->where('payment_status', PaymentStatusEnum::Paid));
    }
}
