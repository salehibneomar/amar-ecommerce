<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Http\Responses\EmailVerifiedResponse;
use App\Http\Responses\PasswordResetCompletedResponse;
use App\Http\Responses\PasswordResetLinkSentResponse;
use App\Http\Responses\VerificationLinkSentResponse;
use App\Models\User;
use App\Services\Auth\EmailVerificationService;
use App\Services\Auth\PasswordResetService;
use Illuminate\Auth\Events\Logout;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\EmailVerificationNotificationSentResponse;
use Laravel\Fortify\Contracts\LogoutResponse;
use Laravel\Fortify\Contracts\PasswordResetResponse;
use Laravel\Fortify\Contracts\SuccessfulPasswordResetLinkRequestResponse;
use Laravel\Fortify\Contracts\VerifyEmailResponse;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(SuccessfulPasswordResetLinkRequestResponse::class, PasswordResetLinkSentResponse::class);
        $this->app->bind(VerifyEmailResponse::class, EmailVerifiedResponse::class);
        $this->app->bind(PasswordResetResponse::class, PasswordResetCompletedResponse::class);
        $this->app->bind(EmailVerificationNotificationSentResponse::class, VerificationLinkSentResponse::class);

        $this->app->instance(LogoutResponse::class, new class implements LogoutResponse
        {
            public function toResponse($request)
            {
                return $request->attributes->get('loggedOutAdmin')
                    ? redirect()->route('login')
                    : redirect()->route('home');
            }
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureActions();
        $this->configureViews();
        $this->configureRateLimiting();

        Event::listen(Logout::class, fn (Logout $event) => request()->attributes->set('loggedOutAdmin', $event->user instanceof User && $event->user->isBackofficeUser()));
    }

    /**
     * Configure Fortify actions.
     */
    private function configureActions(): void
    {
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::authenticateUsing(function (Request $request): ?User {
            $user = User::query()->where('email', $request->string(Fortify::username()))->first();

            return $user?->isBackofficeUser() && Hash::check($request->string('password'), (string) $user->password)
                ? $user
                : null;
        });
    }

    /**
     * Configure Fortify views.
     */
    private function configureViews(): void
    {
        Fortify::loginView(fn () => view('pages::auth.login'));
        Fortify::verifyEmailView(fn () => view('pages::auth.verify-email', [
            'resendAvailableIn' => app(EmailVerificationService::class)->cooldownSeconds(),
        ]));
        Fortify::confirmPasswordView(
            fn () => view('pages::auth.confirm-password'),
        );
        Fortify::resetPasswordView(function (Request $request) {
            try {
                app(PasswordResetService::class)->ensureValidResetLink(
                    config('fortify.passwords'),
                    $request->string('email')->toString(),
                    (string) $request->route('token'),
                    (string) $request->ip(),
                );
            } catch (ValidationException $exception) {
                return redirect()->route('home')->with('error', $exception->validator->errors()->first());
            }

            return view('pages::auth.reset-password');
        });
        Fortify::requestPasswordResetLinkView(
            fn () => view('pages::auth.forgot-password', [
                'resendAvailableIn' => app(PasswordResetService::class)->cooldownSeconds(config('fortify.passwords')),
            ]),
        );
    }

    /**
     * Configure rate limiting.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by(
                $request->session()->get('login.id'),
            );
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(
                Str::lower($request->input(Fortify::username())).
                    '|'.
                    $request->ip(),
            );

            return Limit::perMinute(throttleAttempts('login'))->by($throttleKey);
        });

        RateLimiter::for('verification', fn (Request $request) => Limit::perMinute(throttleAttempts('verification_email'))->by((string) ($request->user()?->getAuthIdentifier() ?? $request->ip())));
    }
}
