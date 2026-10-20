<?php

namespace App\Traits\Catalog;

use App\Enums\Catalog\ProductStatusEnum;
use App\Enums\System\FrontendCacheKeyEnum;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

trait StockTrait
{
    private function takeStock(Product $product, int $quantity): void
    {
        $taken = Product::query()->whereKey($product->id)->where('quantity', '>=', $quantity)->decrement('quantity', $quantity);

        if ($taken === 0) {
            throw ValidationException::withMessages([
                'cart' => __('Only :count of :name are left.', ['count' => (int) Product::query()->whereKey($product->id)->value('quantity'), 'name' => $product->name]),
            ]);
        }

        $this->switchStockStatus($product->id, ProductStatusEnum::InStock, ProductStatusEnum::OutOfStock, 0);
    }

    private function restoreStock(int $productId, int $quantity): void
    {
        $restored = Product::query()->whereKey($productId)->increment('quantity', $quantity);

        if ($restored > 0) {
            $this->switchStockStatus($productId, ProductStatusEnum::OutOfStock, ProductStatusEnum::InStock, $quantity);
        }
    }

    private function switchStockStatus(int $productId, ProductStatusEnum $from, ProductStatusEnum $to, int $whenQuantity): void
    {
        $switched = Product::query()
            ->whereKey($productId)
            ->where('status', $from)
            ->where('quantity', $whenQuantity)
            ->update(['status' => $to]);

        if ($switched > 0) {
            DB::afterCommit(fn () => FrontendCacheKeyEnum::Products->forget());
        }
    }
}
