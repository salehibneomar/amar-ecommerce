<?php

namespace App\Services\Backoffice\Sales;

use App\Enums\Order\ContactModeEnum;
use App\Enums\Order\OrderStatusEnum;
use App\Enums\Order\PaymentMethodEnum;
use App\Enums\Order\PaymentStatusEnum;
use App\Mail\Order\OrderCancelledMail;
use App\Mail\Order\OrderCompletedMail;
use App\Mail\Order\OrderProcessingMail;
use App\Mail\Order\OrderShippedMail;
use App\Models\Order;
use App\Services\Backoffice\Notification\NotificationService;
use App\Services\Mail\MailService;
use App\Services\Payment\PaymentService;
use App\Traits\Catalog\StockTrait;
use App\Traits\Query\TableQueryTrait;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    use StockTrait, TableQueryTrait;

    public function __construct(
        private readonly PaymentService $paymentService,
        private readonly NotificationService $notificationService,
        private readonly MailService $mailService,
    ) {}

    /**
     * @return list<string>
     */
    public function sortableColumns(): array
    {
        return ['id', 'public_order_id', 'total', 'status', 'payment_status', 'created_at'];
    }

    /**
     * @return LengthAwarePaginator<int, Order>
     */
    public function getOrders(
        string $search = '',
        int $perPage = 10,
        ?OrderStatusEnum $status = null,
        ?PaymentStatusEnum $paymentStatus = null,
        string $sortBy = 'created_at',
        string $sortDirection = 'desc',
    ): LengthAwarePaginator {
        return $this->paginateTableQuery(
            query: Order::query()->withCount('items'),
            search: $search,
            searchColumns: ['public_order_id', 'customer_email', 'phone'],
            filters: [
                'status' => $status,
                'payment_status' => $paymentStatus,
            ],
            perPage: $perPage,
            sortBy: $sortBy,
            sortDirection: $sortDirection,
            sortableColumns: $this->sortableColumns(),
        );
    }

    public function getOrderById(int|string $id): Order
    {
        return Order::query()->with(['items.product.media', 'payment', 'completedBy:id,name', 'updatedBy:id,name'])->withCount('items')->withSum('items', 'quantity')->findOrFail($id);
    }

    public function save(
        int|string $id,
        OrderStatusEnum $status,
        PaymentStatusEnum $paymentStatus,
        int $reviewerId,
        ?ContactModeEnum $contactMode = null,
        ?PaymentMethodEnum $paymentMethod = null,
        ?string $transactionId = null,
        ?string $note = null,
        ?int $extraDiscountPercentage = null,
    ): Order {
        $isPaid = $paymentStatus === PaymentStatusEnum::Paid;

        if ($status === OrderStatusEnum::Completed && ! $isPaid) {
            throw ValidationException::withMessages(['orderStatus' => __('Mark the payment as paid to complete the order.')]);
        }

        if ($status === OrderStatusEnum::Cancelled) {
            throw ValidationException::withMessages(['orderStatus' => __('Use the cancel action to cancel an order.')]);
        }

        if ($extraDiscountPercentage !== null && ($extraDiscountPercentage < 0 || $extraDiscountPercentage > shopMaxExtraDiscount())) {
            throw ValidationException::withMessages(['extraDiscountPercentage' => __('The extra discount must be between 0 and :max%.', ['max' => shopMaxExtraDiscount()])]);
        }

        $previousStatus = null;

        $order = DB::transaction(function () use ($id, $status, $paymentStatus, $isPaid, $reviewerId, $contactMode, $paymentMethod, $transactionId, $note, $extraDiscountPercentage, &$previousStatus): Order {
            $order = Order::query()->with('payment')->lockForUpdate()->findOrFail($id);
            $previousStatus = $order->status;

            if ($order->status->isCancelled()) {
                throw ValidationException::withMessages(['orderStatus' => __('Cancelled orders cannot be changed.')]);
            }

            if ($extraDiscountPercentage !== null && $extraDiscountPercentage !== $order->extra_discount_percentage) {
                if (! $order->isExtraDiscountEditable()) {
                    throw ValidationException::withMessages(['extraDiscountPercentage' => __('The extra discount can only be changed on unpaid orders without online payment.')]);
                }

                $extraDiscount = $this->extraDiscountAmount($order->total_before_extra_discount, $extraDiscountPercentage);

                $order->forceFill([
                    'extra_discount_percentage' => $extraDiscountPercentage,
                    'extra_discount_amount' => $extraDiscount,
                    'total' => $order->total_before_extra_discount - $extraDiscount,
                ]);
            }

            $status = $isPaid && $status === OrderStatusEnum::Pending ? OrderStatusEnum::Processing : $status;
            $isCompleted = $status === OrderStatusEnum::Completed;

            $order->forceFill([
                'status' => $status,
                'contact_mode' => $contactMode,
                'customer_contact_recorded_at' => $contactMode ? ($order->customer_contact_recorded_at ?? now()) : null,
                'internal_note' => filled($note) ? $note : null,
                'completed_at' => $isCompleted ? ($order->completed_at ?? now()) : null,
                'completed_by' => $isCompleted ? ($order->completed_by ?? $reviewerId) : null,
            ]);

            $this->paymentService->record($order, $paymentStatus, $paymentMethod, $transactionId);
            $order->save();

            return $order;
        });

        if ($order->status !== $previousStatus) {
            $this->notifyCustomer($order);
            $this->notificationService->notifyOrder($order);
        }

        return $order;
    }

    public function extraDiscountAmount(int $amount, int $percentage): int
    {
        return (int) round($amount * $percentage / 100);
    }

    public function cancel(int|string $id, bool $pendingOnly = false): void
    {
        $order = DB::transaction(function () use ($id, $pendingOnly): Order {
            $order = Order::query()->with('items')->lockForUpdate()->findOrFail($id);

            if ($order->payment_status->isPaid()) {
                throw ValidationException::withMessages(['order' => __('Refund the payment before cancelling the order.')]);
            }

            if ($pendingOnly && ! $order->isCancellableByCustomer()) {
                throw ValidationException::withMessages(['order' => __('Only pending orders can be cancelled.')]);
            }

            if (! $order->isCancellable()) {
                throw ValidationException::withMessages([
                    'order' => __('This order is already :status.', ['status' => strtolower($order->status->label())]),
                ]);
            }

            foreach ($order->items->whereNotNull('product_id') as $item) {
                $this->restoreStock($item->product_id, $item->quantity);
            }

            $order->status = OrderStatusEnum::Cancelled;

            if ($order->payment_status !== PaymentStatusEnum::Refunded) {
                $this->paymentService->record($order, PaymentStatusEnum::Cancelled);
            }

            $order->save();

            return $order;
        });

        $this->notifyCustomer($order);
        $this->notificationService->notifyOrder($order);
    }

    private function notifyCustomer(Order $order): void
    {
        $mail = match ($order->status) {
            OrderStatusEnum::Processing => new OrderProcessingMail($order),
            OrderStatusEnum::Shipped => new OrderShippedMail($order),
            OrderStatusEnum::Completed => new OrderCompletedMail($order),
            OrderStatusEnum::Cancelled => new OrderCancelledMail($order),
            default => null,
        };

        if ($mail === null) {
            return;
        }

        $order->loadMissing('items');

        $this->mailService->send($order->customer_email, $mail);
    }

    public function countUnresolved(): int
    {
        return Order::query()->whereIn('status', OrderStatusEnum::unresolved())->count();
    }
}
