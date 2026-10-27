<?php

namespace App\Services\Frontend\Shop;

use App\Enums\Catalog\ProductStatusEnum;
use App\Models\Offer;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class CartService
{
    private const SESSION_KEY = 'cart';

    public function __construct(private readonly PageService $pageService) {}

    /**
     * @return Collection<int, array{product: Product, quantity: int, line_total: int, original_total: int|null}>
     */
    public function getItems(): Collection
    {
        $quantities = $this->quantities();

        if ($quantities === []) {
            return collect();
        }

        $products = Product::query()
            ->with('media')
            ->whereKey(array_keys($quantities))
            ->where('status', ProductStatusEnum::InStock)
            ->get()
            ->keyBy('id');

        if ($products->count() !== count($quantities)) {
            $this->store(array_intersect_key($quantities, $products->all()));
        }

        return collect(array_keys($quantities))
            ->map(fn (int $productId): ?Product => $products->get($productId))
            ->filter()
            ->map(fn (Product $product): array => [
                'product' => $product,
                'quantity' => $quantities[$product->id],
                'line_total' => $product->final_price * $quantities[$product->id],
                'original_total' => $product->has_discount ? $product->price * $quantities[$product->id] : null,
            ])
            ->values();
    }

    public function count(): int
    {
        return array_sum($this->quantities());
    }

    public function quantityOf(int $productId): int
    {
        return $this->quantities()[$productId] ?? 0;
    }

    public function add(int $productId): void
    {
        $product = Product::query()->whereKey($productId)->where('status', ProductStatusEnum::InStock)->first();

        if ($product === null) {
            throw ValidationException::withMessages(['cart' => __('This product is not available right now.')]);
        }

        $quantities = $this->quantities();
        $quantity = ($quantities[$productId] ?? 0) + 1;

        $this->ensureStock($product, $quantity);

        $quantities[$productId] = $quantity;
        $this->store($quantities);
    }

    public function updateQuantity(int $productId, int $quantity): void
    {
        $quantities = $this->quantities();

        if (! isset($quantities[$productId])) {
            return;
        }

        if ($quantity < 1) {
            $this->remove($productId);

            return;
        }

        $product = Product::query()->findOrFail($productId);

        $this->ensureStock($product, $quantity);

        $quantities[$productId] = $quantity;
        $this->store($quantities);
    }

    public function remove(int $productId): void
    {
        $quantities = $this->quantities();
        unset($quantities[$productId]);
        $this->store($quantities);
    }

    public function clear(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    /**
     * @param  Collection<int, array{product: Product, quantity: int, line_total: int, original_total: int|null}>  $items
     * @return array{subtotal: int, discount: int, total: int, offers: list<array{name: string, percentage: int, categories: list<string>, amount: int}>, itemOffers: array<int, Offer>}
     */
    public function summary(Collection $items): array
    {
        $subtotal = (int) $items->sum('line_total');
        $bestOffers = $this->pageService->bestOffersFor($items->map(fn (array $item): ?int => $item['product']->category_id)->all());
        $groups = [];
        $itemOffers = [];

        foreach ($items as $item) {
            $offer = $bestOffers[(int) $item['product']->category_id] ?? null;

            if ($offer) {
                $itemOffers[$item['product']->id] = $offer;
                $groups[$offer->id]['offer'] = $offer;
                $groups[$offer->id]['subtotal'] = ($groups[$offer->id]['subtotal'] ?? 0) + $item['line_total'];
            }
        }

        $offers = array_values(array_map(fn (array $group): array => $group['offer']->discountSummary((int) round($group['subtotal'] * $group['offer']->discount_percentage / 100)), $groups));
        $discount = array_sum(array_column($offers, 'amount'));

        return [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total' => $subtotal - $discount,
            'offers' => $offers,
            'itemOffers' => $itemOffers,
        ];
    }

    public function ensureStock(Product $product, int $quantity): void
    {
        if ($quantity > $product->quantity) {
            throw ValidationException::withMessages([
                'cart' => __('Only :count of :name are left.', ['count' => $product->quantity, 'name' => $product->name]),
            ]);
        }
    }

    /**
     * @return array<int, int>
     */
    private function quantities(): array
    {
        return (array) session()->get(self::SESSION_KEY, []);
    }

    /**
     * @param  array<int, int>  $quantities
     */
    private function store(array $quantities): void
    {
        session()->put(self::SESSION_KEY, $quantities);
    }
}
