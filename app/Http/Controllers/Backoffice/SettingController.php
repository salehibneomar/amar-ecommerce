<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class SettingController extends Controller
{
    public function index(): View
    {
        return view('backoffice.settings.index');
    }

    public function profile(): View
    {
        return view('backoffice.settings.profile');
    }

    public function appearance(): View
    {
        return view('backoffice.settings.appearance');
    }

    public function security(): View
    {
        return view('backoffice.settings.security');
    }

    public function branding(): View
    {
        return view('backoffice.settings.branding');
    }

    public function system(): View
    {
        return view('backoffice.settings.system');
    }
}
