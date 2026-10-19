<?php

namespace App\Livewire\Frontend\Cart;

use App\Models\Book;
use App\Models\Offer;
use App\Models\User;
use App\Services\Frontend\Shop\CartService;
use App\Services\Frontend\Shop\OrderService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * @property-read Collection<int, array{product: Book, quantity: int, line_total: int, original_total: int|null}> $items
 * @property-read array{subtotal: int, discount: int, total: int, offers: list<array{name: string, percentage: int, categories: list<string>, amount: int}>, itemOffers: array<int, Offer>, requiresShipping: bool} $summary
 */
class Checkout extends Component
{
    public string $phone = '';

    public string $shippingAddress = '';

    public string $notes = '';

    protected CartService $cartService;

    protected OrderService $orderService;

    public function mount(): void
    {
        $user = Auth::user();

        if ($user instanceof User) {
            $this->phone = $user->phone ?? '';
            $this->shippingAddress = $user->address ?? '';
        }
    }

    public function boot(CartService $cartService, OrderService $orderService): void
    {
        $this->cartService = $cartService;
        $this->orderService = $orderService;
    }

    public function placeOrder(): void
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            $this->redirect(route('user.register'), navigate: true);

            return;
        }

        $validated = $this->validate([
            'phone' => ['required', 'string', 'regex:/^(\+?88)?01[3-9]\d{8}$/'],
            'shippingAddress' => [Rule::requiredIf($this->summary['requiresShipping']), 'nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [
            'phone.regex' => __('Enter a valid Bangladeshi mobile number, e.g. 01712345678.'),
        ]);

        $order = $this->orderService->placeOrder($user, [
            'phone' => $validated['phone'],
            'shipping_address' => $validated['shippingAddress'],
            'notes' => $validated['notes'],
        ]);

        $this->dispatch('cart-updated');
        $this->redirect(URL::signedRoute('orders.confirmation', $order->public_order_id), navigate: true);
    }

    /**
     * @return Collection<int, array{product: Book, quantity: int, line_total: int, original_total: int|null}>
     */
    #[Computed]
    public function items(): Collection
    {
        return $this->cartService->getItems();
    }

    /**
     * @return array{subtotal: int, discount: int, total: int, offers: list<array{name: string, percentage: int, categories: list<string>, amount: int}>, itemOffers: array<int, Offer>, requiresShipping: bool}
     */
    #[Computed]
    public function summary(): array
    {
        return $this->cartService->summary($this->items);
    }

    public function render(): View
    {
        return view('components.frontend.cart.checkout');
    }
}
