<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Services\Backoffice\People\StaffPaymentService;
use App\Services\Backoffice\People\StaffService;
use App\Services\Payment\InvoiceService;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpFoundation\Response;

class StaffController extends Controller
{
    public function __construct(
        private readonly StaffService $staffService,
        private readonly StaffPaymentService $staffPaymentService,
        private readonly InvoiceService $invoiceService,
    ) {}

    public function index(): View
    {
        return view('backoffice.staff.index');
    }

    public function payments(int $member): View
    {
        $member = $this->staffService->getStaffById($member);

        return view('backoffice.staff-payments.index', compact('member'));
    }

    public function paymentInvoice(int $member, int $payment): Response
    {
        $member = $this->staffService->getStaffById($member);

        return $this->invoiceService->downloadStaffPayment($this->staffPaymentService->getPaymentById($member->id, $payment));
    }
}
