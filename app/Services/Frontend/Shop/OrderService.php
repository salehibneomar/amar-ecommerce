<?php

namespace App\Services\Frontend\Shop;

use App\Enums\Order\OrderStatusEnum;
use App\Enums\Order\PaymentStatusEnum;
use App\Enums\Payment\PaymentGatewayEnum;
use App\Mail\Order\OrderPlacedMail;
use App\Mail\Order\OrderRequestedMail;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Services\Backoffice\Notification\NotificationService;
use App\Services\Backoffice\Sales\OrderService as StaffOrderService;
use App\Services\Mail\MailService;
use App\Services\Payment\PaymentService;
use App\Traits\Catalog\StockTrait;
use App\Traits\Security\ThrottleTrait;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    use StockTrait, ThrottleTrait;

    private const ORDERS_PER_PAGE = 10;

    private const RECENT_ORDERS_LIMIT = 3;

    private const HELD_CHECKOUT_KEY = 'checkout.held';

    private const PUBLIC_ID_ALPHABET = '23456789ABCDEFGHJKMNPQRSTUVWXYZ';

    public function __construct(
        private readonly CartService $cartService,
        private readonly NotificationService $notificationService,
        private readonly MailService $mailService,
        private readonly PaymentService $paymentService,
        private readonly StaffOrderService $staffOrderService,
    ) {}

    /**
     * @param  array{customer_name: string, customer_email: string, phone: string, shipping_address: string, billing_address: string, notes: string|null}  $data
     */
    public function placeOrder(?User $user, array $data, string $throttleKey, ?PaymentGatewayEnum $gateway = null): Order
    {
        $this->throttle('checkout:'.$throttleKey, throttleAttempts('checkout'), 'cart');

        $order = DB::transaction(function () use ($user, $data, $gateway): Order {
            $items = $this->cartService->getItems();

            if ($items->isEmpty()) {
                throw ValidationException::withMessages(['cart' => __('Your cart is empty.')]);
            }

            $summary = $this->cartService->summary($items);

            $order = new Order;
            $order->forceFill([
                'public_order_id' => $this->generatePublicOrderId(),
                'user_id' => $user?->id,
                'customer_name' => $user->name ?? $data['customer_name'],
                'customer_email' => $user->email ?? strtolower(trim($data['customer_email'])),
                'subtotal' => $summary['subtotal'],
                'discount_amount' => $summary['discount'],
                'applied_offers' => $summary['offers'] ?: null,
                'total' => $summary['total'],
                'currency' => appCurrency(),
                'status' => OrderStatusEnum::Pending,
                'payment_status' => PaymentStatusEnum::Pending,
                'phone' => $data['phone'],
                'shipping_address' => $data['shipping_address'],
                'billing_address' => $data['billing_address'],
                'notes' => filled($data['notes']) ? $data['notes'] : null,
            ])->save();

            foreach ($items as $item) {
                $product = Product::query()->findOrFail($item['product']->id);
                $this->takeStock($product, $item['quantity']);

                $order->items()->create([
                    'product_id' => $product->id,
                    'product_name_snapshot' => $product->name,
                    'sku_snapshot' => $product->sku,
                    'quantity' => $item['quantity'],
                    'unit_price' => $product->price,
                    'discount_percentage' => (int) $product->discount_percentage,
                    'offer_name_snapshot' => ($summary['itemOffers'][$product->id] ?? null)?->name,
                    'offer_discount_percentage' => ($summary['itemOffers'][$product->id] ?? null)->discount_percentage ?? 0,
                    'line_total' => $product->final_price * $item['quantity'],
                ]);
            }

            $user?->forceFill([
                'phone' => $user->phone ?: (User::query()->where('phone', $data['phone'])->exists() ? null : $data['phone']),
                'shipping_address' => $user->shipping_address ?: $data['shipping_address'],
                'billing_address' => $user->billing_address ?: $data['billing_address'],
            ])->save();

            if ($gateway !== null) {
                $order->setRelation('payment', $this->paymentService->open($order, $gateway));
            }

            return $order;
        });

        $this->cartService->clear();
        $this->sendPlacedMails($order->load('items'));
        $this->notificationService->notifyOrder($order);

        return $order;
    }

    /**
     * @param  array{customer_name: string, customer_email: string, phone: string, shipping_address: string, billing_address: string, notes: string|null}  $data
     */
    public function holdCheckout(array $data, PaymentGatewayEnum $gateway): void
    {
        session()->put(self::HELD_CHECKOUT_KEY, ['data' => $data, 'gateway' => $gateway->value]);
    }

    /**
     * @return array{data: array{customer_name: string, customer_email: string, phone: string, shipping_address: string, billing_address: string, notes: string|null}, gateway: PaymentGatewayEnum, view: view-string}|null
     */
    public function heldCheckout(): ?array
    {
        /** @var array{data?: array{customer_name: string, customer_email: string, phone: string, shipping_address: string, billing_address: string, notes: string|null}, gateway?: string} $held */
        $held = (array) session()->get(self::HELD_CHECKOUT_KEY, []);
        $gateway = PaymentGatewayEnum::tryFrom($held['gateway'] ?? '');
        $view = $gateway?->pageView();

        if (! isset($held['data']) || $view === null || ! in_array($gateway, $this->paymentService->gateways(), true) || $this->cartService->count() === 0) {
            return null;
        }

        return ['data' => $held['data'], 'gateway' => $gateway, 'view' => $view];
    }

    public function placeHeldCheckout(?User $user, string $throttleKey): Payment
    {
        $held = $this->heldCheckout() ?? throw ValidationException::withMessages(['checkout' => __('Your checkout has expired. Please check out again.')]);

        $order = $this->placeOrder($user, $held['data'], $throttleKey, $held['gateway']);
        session()->forget(self::HELD_CHECKOUT_KEY);

        return $order->payment;
    }

    /**
     * @return list<array{icon: string, label: string, href: string}>
     */
    public function contactLinks(Order $order): array
    {
        if (! $this->paymentService->isManual($order) || ! $order->status->isUnresolved() || $order->payment_status->isPaid()) {
            return [];
        }

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
        return $order->load(['items.product.media', 'payment']);
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

    /**
     * @return array{total: int, active: int, completed: int}
     */
    public function orderCounts(User $user): array
    {
        $counts = $user->orders()
            ->selectRaw('count(*) as total')
            ->selectRaw('sum(case when status in (?, ?, ?) then 1 else 0 end) as active', [OrderStatusEnum::Pending->value, OrderStatusEnum::Processing->value, OrderStatusEnum::Shipped->value])
            ->selectRaw('sum(case when status = ? then 1 else 0 end) as completed', [OrderStatusEnum::Completed->value])
            ->toBase()
            ->first();

        return ['total' => (int) $counts?->total, 'active' => (int) $counts?->active, 'completed' => (int) $counts?->completed];
    }

    public function getOrderForUser(User $user, Order $order): Order
    {
        abort_unless($order->user_id === $user->id, 404);

        return $this->getOrderWithItems($order);
    }

    public function cancelForUser(User $user, Order $order): void
    {
        abort_unless($order->user_id === $user->id, 404);

        $this->staffOrderService->cancel($order->id, pendingOnly: true);
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
        $this->mailService->send($order->customer_email, new OrderPlacedMail($order, $this->paymentService->isManual($order)));

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
