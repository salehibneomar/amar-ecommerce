<?php

namespace Database\Seeders;

use App\Enums\Order\OrderStatusEnum;
use App\Enums\Order\PaymentMethodEnum;
use App\Enums\Order\PaymentStatusEnum;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $customer = User::query()->where('email', 'customer@example.com')->firstOrFail();
        $products = Product::query()->where('is_free', false)->orderBy('id')->limit(4)->get();

        $this->createOrder($customer, array_values($products->take(2)->all()), paid: true);
        $this->createOrder($customer, array_values($products->slice(2, 1)->all()), paid: false);

        User::query()
            ->whereKeyNot($customer->id)
            ->customers()
            ->limit(4)
            ->get()
            ->each(fn (User $user, int $index) => $this->createOrder($user, [$products[$index % $products->count()]], paid: $index % 2 === 0));
    }

    /**
     * @param  list<Product>  $products
     */
    private function createOrder(User $user, array $products, bool $paid): void
    {
        $subtotal = collect($products)->sum(fn (Product $product): int => $product->final_price);

        $order = Order::factory()
            ->for($user)
            ->state([
                'subtotal' => $subtotal,
                'total' => $subtotal,
                'status' => $paid ? OrderStatusEnum::Completed : OrderStatusEnum::Requested,
                'payment_status' => $paid ? PaymentStatusEnum::Paid : PaymentStatusEnum::Pending,
                'payment_method' => $paid ? PaymentMethodEnum::Bkash : null,
                'transaction_id' => $paid ? Str::upper(Str::random(10)) : null,
                'completed_at' => $paid ? now() : null,
                'phone' => '01700000000',
            ])
            ->create();

        foreach ($products as $product) {
            $order->items()->create([
                'product_id' => $product->id,
                'product_name_snapshot' => $product->name,
                'quantity' => 1,
                'unit_price' => $product->price,
                'discount_percentage' => (int) $product->discount_percentage,
                'line_total' => $product->final_price,
            ]);
        }
    }
}
