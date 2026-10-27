<?php

namespace App\Livewire\Frontend\Cart;

use App\Enums\Payment\PaymentGatewayEnum;
use App\Livewire\Concerns\InteractsWithAnalytics;
use App\Models\Offer;
use App\Models\Product;
use App\Models\User;
use App\Services\Frontend\Account\AccountService;
use App\Services\Frontend\Shop\CartService;
use App\Services\Frontend\Shop\OrderService;
use App\Services\Payment\PaymentService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * @property-read Collection<int, array{product: Product, quantity: int, line_total: int, original_total: int|null}> $items
 * @property-read list<PaymentGatewayEnum> $gateways
 * @property-read array{subtotal: int, discount: int, total: int, offers: list<array{name: string, percentage: int, categories: list<string>, amount: int}>, itemOffers: array<int, Offer>} $summary
 */
class Checkout extends Component
{
    use InteractsWithAnalytics;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $shippingAddress = '';

    public string $billingAddress = '';

    public bool $billingSameAsShipping = true;

    public bool $createAccount = false;

    public string $password = '';

    public string $password_confirmation = '';

    public string $notes = '';

    public string $paymentGateway = '';

    protected CartService $cartService;

    protected OrderService $orderService;

    protected PaymentService $paymentService;

    protected AccountService $accountService;

    public function mount(): void
    {
        $this->paymentGateway = $this->paymentService->gateways()[0]->value ?? '';
        $user = Auth::user();

        if ($user instanceof User) {
            $this->phone = $user->phone ?? '';
            $this->shippingAddress = $user->shipping_address ?? '';
            $this->billingAddress = $user->billing_address ?? '';
            $this->billingSameAsShipping = $this->billingAddress === '' || $this->billingAddress === $this->shippingAddress;
        }
    }

    public function boot(CartService $cartService, OrderService $orderService, PaymentService $paymentService, AccountService $accountService): void
    {
        $this->accountService = $accountService;
        $this->cartService = $cartService;
        $this->orderService = $orderService;
        $this->paymentService = $paymentService;
    }

    public function placeOrder(): void
    {
        $user = Auth::user();
        $user = $user instanceof User ? $user : null;

        $validated = $this->validate([
            'name' => [Rule::requiredIf($user === null), 'nullable', 'string', 'max:255'],
            'email' => [Rule::requiredIf($user === null), 'nullable', 'string', 'email', 'max:255', ...($user === null && $this->createAccount ? ['unique:users,email'] : [])],
            'phone' => ['required', 'string', 'regex:/^(\+?88)?01[3-9]\d{8}$/'],
            'shippingAddress' => ['required', 'string', 'max:500'],
            'billingSameAsShipping' => ['boolean'],
            'billingAddress' => ['exclude_if:billingSameAsShipping,true', 'required', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:500'],
            'paymentGateway' => $this->gateways ? ['required', Rule::enum(PaymentGatewayEnum::class)->only($this->gateways)] : ['exclude'],
            'createAccount' => ['boolean'],
            'password' => $user === null && $this->createAccount ? ['required', 'string', Password::default(), 'confirmed'] : ['exclude'],
        ], [
            'phone.regex' => __('Enter a valid Bangladeshi mobile number, e.g. 01712345678.'),
        ]);

        $data = [
            'customer_name' => (string) $validated['name'],
            'customer_email' => (string) $validated['email'],
            'phone' => $validated['phone'],
            'shipping_address' => $validated['shippingAddress'],
            'billing_address' => $this->billingSameAsShipping ? $validated['shippingAddress'] : $validated['billingAddress'],
            'notes' => $validated['notes'],
        ];
        $gateway = PaymentGatewayEnum::tryFrom($validated['paymentGateway'] ?? '');

        if ($gateway?->pageView() !== null) {
            $this->registerAccount($user, $validated);
            $this->orderService->holdCheckout($data, $gateway);
            $this->redirect(route('payments.checkout'));

            return;
        }

        $order = $this->orderService->placeOrder($user, $data, $user ? 'user:'.$user->id : 'ip:'.request()->ip(), $gateway);
        $this->registerAccount($user, $validated);

        $this->dispatch('cart-updated');

        if ($paymentUrl = $this->paymentService->payUrl($order)) {
            $this->redirect($paymentUrl);

            return;
        }

        $this->redirect(URL::signedRoute('orders.confirmation', $order->public_order_id), navigate: true);
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

    /**
     * @return array{name: string, params: array<string, mixed>}|null
     */
    #[Computed]
    public function analyticsEvent(): ?array
    {
        return $this->analyticsEventService->beginCheckoutEvent($this->items, $this->summary['total']);
    }

    /**
     * @return list<PaymentGatewayEnum>
     */
    #[Computed]
    public function gateways(): array
    {
        return $this->paymentService->gateways();
    }

    public function render(): View
    {
        return view('components.frontend.cart.checkout');
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function registerAccount(?User $user, array $validated): void
    {
        if ($user !== null || ! $this->createAccount) {
            return;
        }

        $this->accountService->register([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $this->password,
            'password_confirmation' => $this->password_confirmation,
        ], (string) request()->ip());
    }
}
