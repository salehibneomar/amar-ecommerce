<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Backoffice\Catalog\BookService;
use App\Services\Frontend\Account\BookLibraryService;
use App\Services\Frontend\Account\BookReaderService;
use App\Services\Frontend\Shop\OrderService;
use App\Services\Frontend\Shop\PageService;
use App\Services\Payment\InvoiceService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AccountController extends Controller
{
    public function __construct(
        private readonly BookService $bookService,
        private readonly BookReaderService $bookReaderService,
        private readonly InvoiceService $invoiceService,
        private readonly OrderService $orderService,
        private readonly BookLibraryService $bookLibraryService,
        private readonly PageService $pageService,
    ) {}

    public function dashboard(Request $request): View
    {
        $user = $request->user();
        $recentOrders = $this->orderService->getRecentOrders($user);
        $ordersCount = $this->orderService->countOrders($user);
        $ownedBooksCount = count($this->bookLibraryService->ownedProductIds($user));
        $recentBooks = $this->bookLibraryService->getRecentBooks($user);
        $openedBooksCount = $this->bookLibraryService->countOpenedBooks($user);
        $newBooks = $this->pageService->getNewArrivals(3);

        return view('frontend.dashboard.index', compact('user', 'recentOrders', 'ordersCount', 'ownedBooksCount', 'recentBooks', 'openedBooksCount', 'newBooks'));
    }

    public function library(Request $request): View
    {
        $books = $this->bookLibraryService->getLibrary($request->user());

        return view('frontend.library.index', compact('books'));
    }

    public function orders(Request $request): View
    {
        $orders = $this->orderService->getOrders($request->user());

        return view('frontend.orders.index', compact('orders'));
    }

    public function orderDetail(Request $request, Order $order): View
    {
        $order = $this->orderService->getOrderForUser($request->user(), $order);
        $contactLinks = $order->status->isUnresolved() ? $this->orderService->contactLinks($order) : [];

        return view('frontend.orders.detail', compact('order', 'contactLinks'));
    }

    public function invoice(Request $request, Order $order): Response
    {
        abort_unless($this->invoiceService->canDownload($order, $request->user()), 404);

        return $this->invoiceService->download($order);
    }

    public function downloadBook(Request $request, int $book): Response
    {
        abort_unless($this->bookReaderService->canDownload($request->user(), $book), 404);

        return $this->bookService->fileResponse($this->bookService->getBookById($book), asAttachment: true);
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
