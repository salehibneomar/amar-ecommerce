<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class SalesReportController extends Controller
{
    public function index(): View
    {
        return view('backoffice.sales-reports.index');
    }
}
