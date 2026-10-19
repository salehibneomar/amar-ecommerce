<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class OfferController extends Controller
{
    public function index(): View
    {
        return view('backoffice.offers.index');
    }
}
