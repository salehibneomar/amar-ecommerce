<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Frontend\Shop\OrderService;
use App\Services\Payment\PaymentService;
use Illuminate\Contracts\View\View;

class CartController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService,
        private readonly PaymentService $paymentService,
    ) {}

    public function checkout(): View
    {
        return view('frontend.cart.checkout');
    }

    public function track(): View
    {
        return view('frontend.orders.track');
    }

    public function confirmation(Order $order): View
    {
        $order = $this->orderService->getOrderWithItems($order);
        $contactLinks = $this->orderService->contactLinks($order);
        $payUrl = $this->paymentService->payUrl($order);

        return view('frontend.cart.confirmation', compact('order', 'contactLinks', 'payUrl'));
    }
}
