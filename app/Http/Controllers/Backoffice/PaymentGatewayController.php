<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class PaymentGatewayController extends Controller
{
    public function edit(): View
    {
        return view('backoffice.settings.payments');
    }
}
