<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class SeoMetaController extends Controller
{
    public function index(): View
    {
        return view('backoffice.seo-metas.index');
    }
}
