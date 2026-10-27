<?php

namespace App\Livewire\Backoffice\Orders;

use App\Enums\Order\ContactModeEnum;
use App\Enums\Order\OrderStatusEnum;
use App\Enums\Order\PaymentMethodEnum;
use App\Enums\Order\PaymentStatusEnum;
use App\Livewire\Concerns\InteractsWithTable;
use App\Models\Order;
use App\Services\Backoffice\Sales\OrderService;
use App\Services\Payment\PaymentService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * @property-read Order|null $selectedOrder
 * @property-read bool $isManualPayment
 */
class Index extends Component
{
    use InteractsWithTable, WithPagination;

    #[Url]
    public string $status = '';

    public string $paymentStatus = '';

    public ?int $selectedOrderId = null;

    public bool $showOrder = false;

    public bool $showDetails = false;

    public bool $showCancelConfirmation = false;

    public string $orderStatus = '';

    public string $orderPaymentStatus = '';

    public string $contactMode = '';

    public string $paymentMethod = '';

    public string $trxId = '';

    public string $internalNote = '';

    public string $extraDiscountPercentage = '';

    protected OrderService $orderService;

    protected PaymentService $paymentService;

    public function boot(OrderService $orderService, PaymentService $paymentService): void
    {
        $this->orderService = $orderService;
        $this->paymentService = $paymentService;
    }

    public function mount(): void
    {
        if ($orderId = request()->integer('order')) {
            $this->viewDetails($orderId);
        }
    }

    protected function sortableColumns(): array
    {
        return $this->orderService->sortableColumns();
    }

    public function updatedStatus(string $status): void
    {
        $this->status = OrderStatusEnum::tryFrom($status)->value ?? '';
        $this->resetTableFilters();
    }

    public function updatedPaymentStatus(string $paymentStatus): void
    {
        $this->paymentStatus = PaymentStatusEnum::tryFrom($paymentStatus)->value ?? '';
        $this->resetTableFilters();
    }

    public function updatedOrderPaymentStatus(): void
    {
        $isPaid = $this->orderPaymentStatus === PaymentStatusEnum::Paid->value;

        if ($isPaid && $this->orderStatus === OrderStatusEnum::Pending->value) {
            $this->orderStatus = OrderStatusEnum::Processing->value;
        } elseif (! $isPaid && $this->orderStatus === OrderStatusEnum::Completed->value) {
            $this->orderStatus = OrderStatusEnum::Shipped->value;
        }

        unset($this->editableStatusOptions);
    }

    public function updatedExtraDiscountPercentage(): void
    {
        unset($this->extraDiscountPreview);
    }

    public function updatedShowOrder(bool $showOrder): void
    {
        if (! $showOrder) {
            $this->resetValidation();
        }
    }

    public function viewDetails(int $orderId): void
    {
        $this->selectedOrderId = $this->orderService->getOrderById($orderId)->id;
        unset($this->selectedOrder);
        $this->showDetails = true;
    }

    public function viewOrder(int $orderId): void
    {
        $order = $this->orderService->getOrderById($orderId);

        $this->selectedOrderId = $order->id;
        $this->orderStatus = $order->status->value;
        $this->orderPaymentStatus = $order->payment_status->value;
        $this->contactMode = $order->contact_mode->value ?? '';
        $this->paymentMethod = (string) $order->payment?->method?->value;
        $this->trxId = (string) $order->payment?->transaction_id;
        $this->internalNote = (string) $order->internal_note;
        $this->extraDiscountPercentage = $order->extra_discount_percentage > 0 ? (string) $order->extra_discount_percentage : '';
        unset($this->selectedOrder);
        $this->resetValidation();
        $this->showDetails = false;
        $this->showOrder = true;
    }

    public function save(): void
    {
        $isManual = $this->isManualPayment;

        $validated = $this->validate([
            'orderStatus' => ['required', Rule::enum(OrderStatusEnum::class)],
            'orderPaymentStatus' => ['required', Rule::enum(PaymentStatusEnum::class)],
            'contactMode' => ['nullable', Rule::enum(ContactModeEnum::class)],
            'paymentMethod' => [Rule::requiredIf($isManual), 'nullable', Rule::enum(PaymentMethodEnum::class)],
            'trxId' => $isManual && (filled($this->trxId) || $this->orderPaymentStatus === PaymentStatusEnum::Paid->value)
                ? $this->paymentService->transactionIdRules(PaymentMethodEnum::tryFrom($this->paymentMethod), $this->selectedOrderId)
                : ['nullable'],
            'internalNote' => ['nullable', 'string', 'max:1000'],
            'extraDiscountPercentage' => ['nullable', 'integer', 'min:0', 'max:'.shopMaxExtraDiscount()],
        ]);

        $this->orderService->save(
            (int) $this->selectedOrderId,
            OrderStatusEnum::from($validated['orderStatus']),
            PaymentStatusEnum::from($validated['orderPaymentStatus']),
            (int) Auth::id(),
            contactMode: $isManual ? ContactModeEnum::tryFrom((string) $validated['contactMode']) : null,
            paymentMethod: $isManual ? PaymentMethodEnum::tryFrom((string) $validated['paymentMethod']) : null,
            transactionId: $isManual ? $validated['trxId'] : null,
            note: $validated['internalNote'],
            extraDiscountPercentage: (int) $validated['extraDiscountPercentage'],
        );

        $this->showOrder = false;
        $this->refreshOrders(__('Order saved.'));
    }

    public function cancelOrder(): void
    {
        $this->orderService->cancel((int) $this->selectedOrderId);

        $this->showCancelConfirmation = false;
        $this->showOrder = false;
        $this->refreshOrders(__('Order cancelled.'));
    }

    /**
     * @return LengthAwarePaginator<int, Order>
     */
    #[Computed]
    public function orders(): LengthAwarePaginator
    {
        return $this->orderService->getOrders(
            search: $this->search,
            perPage: $this->perPage,
            status: OrderStatusEnum::tryFrom($this->status),
            paymentStatus: PaymentStatusEnum::tryFrom($this->paymentStatus),
            sortBy: $this->sortBy,
            sortDirection: $this->sortDirection,
        );
    }

    #[Computed]
    public function selectedOrder(): ?Order
    {
        return $this->selectedOrderId ? $this->orderService->getOrderById($this->selectedOrderId) : null;
    }

    /**
     * @return array{percentage: int, amount: int, total: int}
     */
    #[Computed]
    public function extraDiscountPreview(): array
    {
        $order = $this->selectedOrder;
        $percentage = filter_var($this->extraDiscountPercentage ?: 0, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => shopMaxExtraDiscount()]]);

        if ($order === null || $percentage === false || ! $order->isExtraDiscountEditable()) {
            return ['percentage' => $order->extra_discount_percentage ?? 0, 'amount' => $order->extra_discount_amount ?? 0, 'total' => $order->total ?? 0];
        }

        $amount = $this->orderService->extraDiscountAmount($order->total_before_extra_discount, $percentage);

        return ['percentage' => $percentage, 'amount' => $amount, 'total' => $order->total_before_extra_discount - $amount];
    }

    /**
     * @return array<string, string>
     */
    #[Computed]
    public function statusOptions(): array
    {
        return collect(OrderStatusEnum::cases())->mapWithKeys(fn (OrderStatusEnum $status): array => [$status->value => $status->label()])->all();
    }

    /**
     * @return array<string, array{label: string, disabled: bool}>
     */
    #[Computed]
    public function editableStatusOptions(): array
    {
        $isPaid = $this->orderPaymentStatus === PaymentStatusEnum::Paid->value;

        return collect(OrderStatusEnum::flow())
            ->mapWithKeys(fn (OrderStatusEnum $status): array => [$status->value => [
                'label' => $status->label(),
                'disabled' => $status === OrderStatusEnum::Completed && ! $isPaid,
            ]])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    #[Computed]
    public function paymentStatusOptions(): array
    {
        return collect(PaymentStatusEnum::cases())->mapWithKeys(fn (PaymentStatusEnum $status): array => [$status->value => $status->label()])->all();
    }

    /**
     * @return array<string, string>
     */
    #[Computed]
    public function contactModeOptions(): array
    {
        return collect(ContactModeEnum::cases())->mapWithKeys(fn (ContactModeEnum $mode): array => [$mode->value => $mode->label()])->all();
    }

    /**
     * @return array<string, string>
     */
    #[Computed]
    public function paymentMethodOptions(): array
    {
        return PaymentMethodEnum::options();
    }

    #[Computed]
    public function isManualPayment(): bool
    {
        return $this->selectedOrder !== null && $this->paymentService->isManual($this->selectedOrder);
    }

    private function refreshOrders(string $message): void
    {
        unset($this->selectedOrder, $this->orders);
        $this->dispatch('toast-show', slots: ['text' => $message], dataset: ['variant' => 'success']);
    }

    public function render(): View
    {
        return view('components.backoffice.orders.index');
    }
}
