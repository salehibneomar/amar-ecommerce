<?php

namespace App\Services\Frontend\Shop;

use App\Enums\Order\OrderStatusEnum;
use App\Enums\Order\PaymentStatusEnum;
use App\Mail\Order\OrderPlacedMail;
use App\Mail\Order\OrderRequestedMail;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\Backoffice\Notification\NotificationService;
use App\Services\Mail\MailService;
use App\Traits\Catalog\StockTrait;
use App\Traits\Security\ThrottleTrait;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    use StockTrait, ThrottleTrait;

    private const ORDERS_PER_PAGE = 10;

    private const RECENT_ORDERS_LIMIT = 3;

    private const PUBLIC_ID_ALPHABET = '23456789ABCDEFGHJKMNPQRSTUVWXYZ';

    public function __construct(
        private readonly CartService $cartService,
        private readonly NotificationService $notificationService,
        private readonly MailService $mailService,
    ) {}

    /**
     * @param  array{phone: string, shipping_address: string|null, notes: string|null}  $data
     */
    public function placeOrder(User $user, array $data): Order
    {
        $this->throttle('checkout:'.$user->id, throttleAttempts('checkout'), 'cart');

        $order = DB::transaction(function () use ($user, $data): Order {
            $items = $this->cartService->getItems();

            if ($items->isEmpty()) {
                throw ValidationException::withMessages(['cart' => __('Your cart is empty.')]);
            }

            $summary = $this->cartService->summary($items);

            if ($summary['requiresShipping'] && blank($data['shipping_address'])) {
                throw ValidationException::withMessages(['shippingAddress' => __('A delivery address is required for printed books.')]);
            }

            $order = new Order;
            $order->forceFill([
                'public_order_id' => $this->generatePublicOrderId(),
                'user_id' => $user->id,
                'customer_name' => $user->name,
                'customer_email' => $user->email,
                'subtotal' => $summary['subtotal'],
                'discount_amount' => $summary['discount'],
                'applied_offers' => $summary['offers'] ?: null,
                'total' => $summary['total'],
                'status' => OrderStatusEnum::Requested,
                'payment_status' => PaymentStatusEnum::Pending,
                'phone' => $data['phone'],
                'shipping_address' => $summary['requiresShipping'] ? $data['shipping_address'] : null,
                'notes' => filled($data['notes']) ? $data['notes'] : null,
            ])->save();

            foreach ($items as $item) {
                $product = Product::query()->findOrFail($item['product']->id);
                $this->takeStock($product, $item['quantity']);

                $order->items()->create([
                    'product_id' => $product->id,
                    'product_name_snapshot' => $product->name,
                    'quantity' => $item['quantity'],
                    'unit_price' => $product->price,
                    'discount_percentage' => (int) $product->discount_percentage,
                    'offer_name_snapshot' => ($summary['itemOffers'][$product->id] ?? null)?->name,
                    'offer_discount_percentage' => ($summary['itemOffers'][$product->id] ?? null)->discount_percentage ?? 0,
                    'line_total' => $product->final_price * $item['quantity'],
                ]);
            }

            $user->forceFill([
                'phone' => $user->phone ?: (User::query()->where('phone', $data['phone'])->exists() ? null : $data['phone']),
                'address' => $user->address ?: ($summary['requiresShipping'] ? $data['shipping_address'] : null),
            ])->save();

            return $order;
        });

        $this->cartService->clear();
        $this->sendPlacedMails($order->load('items'));
        $this->notificationService->notifyOrder($order);

        return $order;
    }

    /**
     * @return list<array{icon: string, label: string, href: string}>
     */
    public function contactLinks(Order $order): array
    {
        $message = __("Hi!\nI am :name, my email is :email\nI have placed an order on :app.\n\nMy Order ID: :order\nTotal: :total\n\nPlease share the payment process. Thank you!", [
            'name' => $order->customer_name,
            'email' => $order->customer_email,
            'app' => config('app.name'),
            'order' => $order->public_order_id,
            'total' => formatMoney($order->total),
        ]);
        $whatsappNumber = shopWhatsappNumber();
        $messengerUrl = shopMessengerUrl();
        $supportEmail = shopSupportEmail();

        return array_values(array_filter([
            $whatsappNumber ? ['icon' => 'chat', 'label' => __('WhatsApp'), 'href' => "https://wa.me/{$whatsappNumber}?".$this->encodeQuery(['text' => $message])] : null,
            $messengerUrl ? ['icon' => 'forum', 'label' => __('Messenger'), 'href' => $messengerUrl.'?'.$this->encodeQuery(['text' => $message])] : null,
            $supportEmail ? ['icon' => 'mail', 'label' => __('Email'), 'href' => "mailto:{$supportEmail}?".$this->encodeQuery([
                'subject' => __('Order :order', ['order' => $order->public_order_id]),
                'body' => $message,
            ])] : null,
        ]));
    }

    public function getOrderWithItems(Order $order): Order
    {
        $order->load('items.product');

        $order->items->pluck('product')
            ->filter()
            ->groupBy(fn (Product $product): string => $product::class)
            ->each(fn (SupportCollection $products): Collection => Collection::make($products)->load('media'));

        return $order;
    }

    /**
     * @return LengthAwarePaginator<int, Order>
     */
    public function getOrders(User $user): LengthAwarePaginator
    {
        return $user->orders()->withCount('items')->latest()->paginate(self::ORDERS_PER_PAGE);
    }

    /**
     * @return Collection<int, Order>
     */
    public function getRecentOrders(User $user): Collection
    {
        return $user->orders()->withCount('items')->latest()->limit(self::RECENT_ORDERS_LIMIT)->get();
    }

    public function countOrders(User $user): int
    {
        return $user->orders()->count();
    }

    public function getOrderForUser(User $user, Order $order): Order
    {
        abort_unless($order->user_id === $user->id, 404);

        return $this->getOrderWithItems($order);
    }

    public function findForTracking(string $publicOrderId, string $email, string $ip): ?Order
    {
        $this->throttle('track-order:'.$ip, throttleAttempts('track_order'), 'orderId');

        $order = Order::query()
            ->where('public_order_id', strtoupper(trim($publicOrderId)))
            ->where('customer_email', strtolower(trim($email)))
            ->first();

        return $order ? $this->getOrderWithItems($order) : null;
    }

    public function getTrackedOrder(int $id): Order
    {
        return $this->getOrderWithItems(Order::query()->findOrFail($id));
    }

    public function claimGuestOrders(User $user): int
    {
        if (! $user->hasVerifiedEmail()) {
            return 0;
        }

        return Order::query()
            ->whereNull('user_id')
            ->where('customer_email', strtolower($user->email))
            ->update(['user_id' => $user->id]);
    }

    private function sendPlacedMails(Order $order): void
    {
        $this->mailService->send($order->customer_email, new OrderPlacedMail($order));

        if ($ownerEmail = shopOwnerEmail()) {
            $this->mailService->send($ownerEmail, new OrderRequestedMail($order));
        }
    }

    private function generatePublicOrderId(): string
    {
        do {
            $id = shopOrderPrefix().'-'.collect(range(1, 6))
                ->map(fn (): string => self::PUBLIC_ID_ALPHABET[random_int(0, strlen(self::PUBLIC_ID_ALPHABET) - 1)])
                ->implode('');
        } while (Order::query()->where('public_order_id', $id)->exists());

        return $id;
    }

    /**
     * @param  array<string, string>  $query
     */
    private function encodeQuery(array $query): string
    {
        return http_build_query($query, encoding_type: PHP_QUERY_RFC3986);
    }
}
