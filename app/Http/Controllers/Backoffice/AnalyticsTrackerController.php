<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class AnalyticsTrackerController extends Controller
{
    public function index(): View
    {
        return view('backoffice.analytics-trackers.index');
    }
}
