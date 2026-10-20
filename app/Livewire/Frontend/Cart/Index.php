<?php

namespace App\Livewire\Frontend\Cart;

use App\Models\Offer;
use App\Models\Product;
use App\Services\Frontend\Shop\CartService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * @property-read Collection<int, array{product: Product, quantity: int, line_total: int, original_total: int|null}> $items
 */
class Index extends Component
{
    protected CartService $cartService;

    public function boot(CartService $cartService): void
    {
        $this->cartService = $cartService;
    }

    public function updateQuantity(int $productId, int $quantity): void
    {
        try {
            $this->cartService->updateQuantity($productId, $quantity);
        } catch (ValidationException $exception) {
            $this->dispatch('toast-show', slots: ['text' => $exception->validator->errors()->first()], dataset: ['variant' => 'danger']);
        }

        $this->refreshCart();
    }

    public function remove(int $productId): void
    {
        $this->cartService->remove($productId);
        $this->refreshCart();
    }

    #[On('cart-updated')]
    public function refresh(): void
    {
        unset($this->items, $this->summary);
    }

    /**
     * @return Collection<int, array{product: Product, quantity: int, line_total: int, original_total: int|null}>
     */
    #[Computed]
    public function items(): Collection
    {
        return $this->cartService->getItems();
    }

    /**
     * @return array{subtotal: int, discount: int, total: int, offers: list<array{name: string, percentage: int, categories: list<string>, amount: int}>, itemOffers: array<int, Offer>}
     */
    #[Computed]
    public function summary(): array
    {
        return $this->cartService->summary($this->items);
    }

    public function render(): View
    {
        return view('components.frontend.cart.index');
    }

    private function refreshCart(): void
    {
        $this->refresh();
        $this->dispatch('cart-updated');
    }
}
