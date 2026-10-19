<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Services\Backoffice\People\StaffService;
use Illuminate\Contracts\View\View;

class StaffController extends Controller
{
    public function __construct(private readonly StaffService $staffService) {}

    public function index(): View
    {
        return view('backoffice.staff.index');
    }

    public function payments(int $member): View
    {
        $member = $this->staffService->getStaffById($member);

        return view('backoffice.staff-payments.index', compact('member'));
    }
}
