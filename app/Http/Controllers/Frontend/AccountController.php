<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Frontend\Shop\OrderService;
use App\Services\Frontend\Shop\PageService;
use App\Services\Payment\InvoiceService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class AccountController extends Controller
{
    public function __construct(
        private readonly InvoiceService $invoiceService,
        private readonly OrderService $orderService,
        private readonly PageService $pageService,
    ) {}

    public function dashboard(Request $request): View
    {
        $user = $request->user();
        $recentOrders = $this->orderService->getRecentOrders($user);
        $orderCounts = $this->orderService->orderCounts($user);
        $newProducts = $this->pageService->getNewArrivals(3);

        return view('frontend.dashboard.index', compact('user', 'recentOrders', 'orderCounts', 'newProducts'));
    }

    public function orders(Request $request): View
    {
        $orders = $this->orderService->getOrders($request->user());

        return view('frontend.orders.index', compact('orders'));
    }

    public function orderDetail(Request $request, Order $order): View
    {
        $order = $this->orderService->getOrderForUser($request->user(), $order);
        $contactLinks = $this->orderService->contactLinks($order);

        return view('frontend.orders.detail', compact('order', 'contactLinks'));
    }

    public function cancelOrder(Request $request, Order $order): RedirectResponse
    {
        try {
            $this->orderService->cancelForUser($request->user(), $order);
        } catch (ValidationException $exception) {
            return back()->with('error', $exception->validator->errors()->first());
        }

        return back()->with('success', __('Order cancelled successfully.'));
    }

    public function invoice(Request $request, Order $order): Response
    {
        abort_unless($this->invoiceService->canDownload($order, $request->user()), 404);

        return $this->invoiceService->download($order);
    }

    public function profile(): View
    {
        return view('frontend.profile.index');
    }

    public function settings(): View
    {
        return view('frontend.settings.index');
    }

    public function notifications(): View
    {
        return view('frontend.notifications.index');
    }
}
