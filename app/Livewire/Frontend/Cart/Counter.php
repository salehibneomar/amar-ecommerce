<?php

namespace App\Livewire\Frontend\Cart;

use App\Services\Frontend\Shop\CartService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class Counter extends Component
{
    protected CartService $cartService;

    public function boot(CartService $cartService): void
    {
        $this->cartService = $cartService;
    }

    #[On('add-to-cart')]
    public function add(int $productId, bool $buyNow = false): void
    {
        $user = Auth::user();

        try {
            $this->cartService->add($user, $productId);
        } catch (ValidationException $exception) {
            $this->toast($exception->validator->errors()->first(), 'danger');

            return;
        }

        unset($this->count);

        if ($buyNow) {
            $this->redirectRoute('checkout', navigate: true);

            return;
        }

        $this->dispatch('cart-updated');
        $this->toast(__('Added to your cart.'), 'success');
    }

    #[On('cart-updated')]
    public function refreshCount(): void
    {
        unset($this->count);
    }

    #[Computed]
    public function count(): int
    {
        return $this->cartService->count();
    }

    public function render(): View
    {
        return view('components.frontend.cart.counter');
    }

    private function toast(string $text, string $variant): void
    {
        $this->dispatch('toast-show', slots: ['text' => $text], dataset: ['variant' => $variant]);
    }
}
