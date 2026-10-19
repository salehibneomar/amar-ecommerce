<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Services\Auth\EmailVerificationService;
use App\Services\Auth\PasswordResetService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(
        private readonly EmailVerificationService $emailVerificationService,
        private readonly PasswordResetService $passwordResetService,
    ) {}

    public function login(): View
    {
        return view('frontend.auth.login');
    }

    public function register(): View
    {
        return view('frontend.auth.register');
    }

    public function forgotPassword(): View
    {
        return view('frontend.auth.forgot-password');
    }

    public function resetPassword(Request $request, string $token): View|RedirectResponse
    {
        try {
            $this->passwordResetService->ensureValidResetLink(config('auth.defaults.passwords'), $request->string('email')->toString(), $token, (string) $request->ip());
        } catch (ValidationException $exception) {
            return redirect()->route('home')->with('error', $exception->validator->errors()->first());
        }

        return view('frontend.auth.reset-password', compact('token'));
    }

    public function verifyEmailNotice(): View
    {
        return view('frontend.auth.verify-email');
    }

    public function verifyEmail(Request $request, int $id, string $hash): RedirectResponse
    {
        $user = $request->hasValidSignature() ? $this->emailVerificationService->verify($id, $hash) : null;

        if ($user === null) {
            $route = match (true) {
                $request->user() === null => 'user.login',
                $request->user()->isBackofficeUser() => 'dashboard',
                default => 'user.dashboard',
            };

            return redirect()->route($route)->with('error', __('This verification link has expired or is invalid.'));
        }

        $justVerified = $user->wasChanged('email_verified_at');
        $variant = $justVerified ? 'success' : 'info';

        if ($request->user()?->is($user)) {
            return redirect()->route($user->isBackofficeUser() ? 'dashboard' : 'user.dashboard', ['verified' => 1])
                ->with($variant, $justVerified ? __('Your email is verified.') : __('Your email is already verified.'));
        }

        return redirect()->route($user->isBackofficeUser() ? 'login' : 'user.login')
            ->with($variant, $justVerified ? __('Your email is verified. You can log in now.') : __('Your email is already verified. You can log in now.'));
    }
}
