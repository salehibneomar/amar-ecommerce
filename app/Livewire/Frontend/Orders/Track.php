<?php

namespace App\Livewire\Frontend\Orders;

use App\Models\Order;
use App\Services\Frontend\Shop\OrderService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * @property-read Order|null $order
 */
class Track extends Component
{
    #[Url(as: 'order', except: '')]
    public string $orderId = '';

    public string $email = '';

    #[Locked]
    public ?int $foundOrderId = null;

    protected OrderService $orderService;

    public function boot(OrderService $orderService): void
    {
        $this->orderService = $orderService;
    }

    public function track(): void
    {
        $validated = $this->validate([
            'orderId' => ['required', 'string', 'max:20'],
            'email' => ['required', 'string', 'email', 'max:255'],
        ]);

        $order = $this->orderService->findForTracking($validated['orderId'], $validated['email'], (string) request()->ip());

        $this->foundOrderId = $order?->id;
        unset($this->order, $this->contactLinks);

        if ($order === null) {
            $this->addError('orderId', __('We could not find an order with this ID and email.'));
        }
    }

    #[Computed]
    public function order(): ?Order
    {
        return $this->foundOrderId ? $this->orderService->getTrackedOrder($this->foundOrderId) : null;
    }

    /**
     * @return list<array{icon: string, label: string, href: string}>
     */
    #[Computed]
    public function contactLinks(): array
    {
        return $this->order ? $this->orderService->contactLinks($this->order) : [];
    }

    public function render(): View
    {
        return view('components.frontend.orders.track');
    }
}
