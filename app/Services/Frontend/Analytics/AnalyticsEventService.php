<?php

namespace App\Services\Frontend\Analytics;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\Backoffice\System\AnalyticsTrackerService;
use Illuminate\Support\Collection;

class AnalyticsEventService
{
    private const TRACKED_PURCHASES_KEY = 'analytics.purchases';

    public function __construct(private readonly AnalyticsTrackerService $analyticsTrackerService) {}

    /**
     * @param  iterable<Product>  $products
     * @return array{name: string, params: array<string, mixed>}|null
     */
    public function viewItemListEvent(iterable $products, string $listName): ?array
    {
        if (! $this->analyticsTrackerService->isTracking()) {
            return null;
        }

        $items = collect($products)->values()->map(fn (Product $product, int $index): array => [...$this->productItem($product), 'index' => $index])->all();

        return ['name' => 'view_item_list', 'params' => ['item_list_name' => $listName, 'items' => $items]];
    }

    /**
     * @return array{name: string, params: array<string, mixed>}|null
     */
    public function viewItemEvent(Product $product): ?array
    {
        return $this->event('view_item', $product->final_price, [$this->productItem($product)]);
    }

    /**
     * @return array{name: string, params: array<string, mixed>}|null
     */
    public function cartChangeEvent(int $productId, int $quantityChange): ?array
    {
        if ($quantityChange === 0 || ! $this->analyticsTrackerService->isTracking()) {
            return null;
        }

        $product = Product::query()->select(['id', 'sku', 'name', 'price', 'discount_percentage'])->find($productId);

        if ($product === null) {
            return null;
        }

        $quantity = abs($quantityChange);

        return $this->event($quantityChange > 0 ? 'add_to_cart' : 'remove_from_cart', $product->final_price * $quantity, [$this->productItem($product, $quantity)]);
    }

    /**
     * @template TProduct of Product
     *
     * @param  Collection<int, array{product: TProduct, quantity: int, line_total: int, original_total: int|null}>  $items
     * @return array{name: string, params: array<string, mixed>}|null
     */
    public function beginCheckoutEvent(Collection $items, int $total): ?array
    {
        return $this->event('begin_checkout', $total, $items->map(fn (array $item): array => $this->productItem($item['product'], $item['quantity']))->values()->all());
    }

    /**
     * @return array{name: string, params: array<string, mixed>}|null
     */
    public function purchaseEvent(Order $order): ?array
    {
        if (in_array($order->id, (array) session(self::TRACKED_PURCHASES_KEY, []), true)) {
            return null;
        }

        $items = $order->items->map(fn (OrderItem $item): array => [
            'item_id' => $item->sku_snapshot ?? (string) $item->product_id,
            'item_name' => $item->product_name_snapshot,
            'price' => intdiv($item->line_total, max($item->quantity, 1)),
            'quantity' => $item->quantity,
        ])->values()->all();

        $event = $this->event('purchase', $order->total, $items, [
            'transaction_id' => $order->public_order_id,
            'currency' => $order->currency,
            'discount' => $order->discount_amount + $order->extra_discount_amount,
        ]);

        if ($event !== null) {
            session()->push(self::TRACKED_PURCHASES_KEY, $order->id);
        }

        return $event;
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @param  array<string, mixed>  $extra
     * @return array{name: string, params: array<string, mixed>}|null
     */
    private function event(string $event, int $value, array $items, array $extra = []): ?array
    {
        if (! $this->analyticsTrackerService->isTracking()) {
            return null;
        }

        return ['name' => $event, 'params' => ['currency' => appCurrency(), 'value' => $value, ...$extra, 'items' => $items]];
    }

    /**
     * @return array{item_id: string, item_name: string, price: int, discount: int, quantity: int}
     */
    private function productItem(Product $product, int $quantity = 1): array
    {
        return [
            'item_id' => $product->sku,
            'item_name' => $product->name,
            'price' => $product->final_price,
            'discount' => $product->price - $product->final_price,
            'quantity' => $quantity,
        ];
    }
}
