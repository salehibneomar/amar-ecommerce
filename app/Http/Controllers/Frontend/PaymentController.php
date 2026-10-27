<?php

namespace App\Http\Controllers\Frontend;

use App\Enums\Order\PaymentStatusEnum;
use App\Enums\Payment\PaymentGatewayEnum;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\Frontend\Shop\CartService;
use App\Services\Frontend\Shop\OrderService;
use App\Services\Payment\PaymentService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\URL;

class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentService $paymentService,
        private readonly OrderService $orderService,
        private readonly CartService $cartService,
    ) {}

    public function show(Payment $payment): View|RedirectResponse
    {
        $orderUrl = URL::signedRoute('orders.confirmation', $payment->order->public_order_id);
        $view = $payment->gateway->pageView();

        if (! $this->paymentService->isPayable($payment)) {
            return redirect($orderUrl);
        }

        if ($view === null) {
            $hostedUrl = $this->paymentService->hostedUrl($payment);

            return $hostedUrl ? redirect()->away($hostedUrl) : redirect($orderUrl)->with('error', __(':gateway is not available right now. Please try again later.', ['gateway' => $payment->gateway->label()]));
        }

        $order = $this->orderService->getOrderWithItems($payment->order);
        $page = $this->paymentService->page($payment);

        return view($view, compact('order', 'page', 'orderUrl'));
    }

    public function checkout(Request $request): View|RedirectResponse
    {
        $held = $this->orderService->heldCheckout();

        if ($held === null) {
            return redirect()->route('checkout');
        }

        $items = $this->cartService->getItems();
        $summary = $this->cartService->summary($items);
        $page = $this->paymentService->checkoutPage($held['gateway'], $summary['total'], $request->user()->email ?? $held['data']['customer_email']);

        return view($held['view'], compact('items', 'summary', 'page'));
    }

    public function place(Request $request): JsonResponse
    {
        $user = $request->user();
        $payment = $this->orderService->placeHeldCheckout($user, $user ? 'user:'.$user->id : 'ip:'.$request->ip());
        $page = $this->paymentService->page($payment);
        $orderUrl = URL::signedRoute('orders.confirmation', $payment->order->public_order_id);

        if ($page['unavailable'] ?? false) {
            return response()->json(['message' => __(':gateway is not available right now. Your order is saved, so you can pay from the order page.', ['gateway' => $payment->gateway->label()]), 'redirect' => $orderUrl], 503);
        }

        return response()->json([
            'clientSecret' => $page['clientSecret'],
            'returnUrl' => $page['returnUrl'],
            'orderUrl' => $orderUrl,
        ]);
    }

    public function return(Payment $payment): RedirectResponse
    {
        $status = $this->paymentService->sync($payment);

        return redirect(URL::signedRoute('orders.confirmation', $payment->order->public_order_id))->with(...match ($status) {
            PaymentStatusEnum::Paid => ['success', __('Payment received. Thank you!')],
            PaymentStatusEnum::Pending => ['info', __('Your payment is being processed. We will update the order soon.')],
            default => ['error', __('Your payment did not go through. Please try again.')],
        });
    }

    public function callback(Payment $payment): RedirectResponse
    {
        return redirect()->route('payments.return', $payment->reference, 303);
    }

    public function webhook(PaymentGatewayEnum $gateway, Request $request): Response
    {
        $this->paymentService->handleWebhook($gateway, $request);

        return response()->noContent();
    }
}
