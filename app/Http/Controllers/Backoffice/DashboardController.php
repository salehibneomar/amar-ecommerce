<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Services\Backoffice\Sales\DashboardService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboardService) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        ['stats' => $stats, 'panels' => $panels] = $this->dashboardService->forUser($user);

        return view('dashboard', compact('user', 'stats', 'panels'));
    }
}
