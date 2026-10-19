<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Payment\InvoiceService;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpFoundation\Response;

class OrderController extends Controller
{
    public function __construct(private readonly InvoiceService $invoiceService) {}

    public function index(): View
    {
        return view('backoffice.orders.index');
    }

    public function invoice(Order $order): Response
    {
        abort_unless($this->invoiceService->canDownload($order), 404);

        return $this->invoiceService->download($order);
    }
}
