<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Backoffice\People\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(private readonly UserService $userService) {}

    public function index(Request $request): View
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
        ]);
        $search = $validated['search'] ?? '';

        return view('backoffice.users.index', compact('search'));
    }

    public function block(User $user): RedirectResponse
    {
        $this->authorize('block', $user);
        $this->userService->block($user);

        return back()->with('success', __('User blocked successfully.'));
    }

    public function unblock(User $user): RedirectResponse
    {
        $this->authorize('unblock', $user);
        $this->userService->unblock($user);

        return back()->with('success', __('User unblocked successfully.'));
    }
}
