<?php

namespace App\Http\Controllers\Auth;

use App\Enums\User\SocialProviderEnum;
use App\Http\Controllers\Controller;
use App\Services\Auth\SocialAuthService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;

class SocialAuthController extends Controller
{
    public function __construct(private readonly SocialAuthService $socialAuthService) {}

    public function redirect(SocialProviderEnum $provider): SymfonyRedirectResponse
    {
        if (! $provider->isConfigured()) {
            return redirect()->route('user.login')->withErrors([
                'email' => __(':provider login is not available yet.', ['provider' => $provider->label()]),
            ]);
        }

        return Socialite::driver($provider->value)->redirect();
    }

    public function callback(Request $request, SocialProviderEnum $provider): RedirectResponse
    {
        abort_unless($provider->isConfigured(), 404);

        try {
            $socialUser = Socialite::driver($provider->value)->user();
        } catch (Exception) {
            return redirect()->route('user.login')->withErrors([
                'email' => __('Unable to sign in with :provider. Please try again.', ['provider' => $provider->label()]),
            ]);
        }

        try {
            $user = $this->socialAuthService->resolveUser($provider, $socialUser);
        } catch (ValidationException $exception) {
            return redirect()->route('user.login')->withErrors($exception->errors());
        }

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->intended(route('user.dashboard'));
    }
}
