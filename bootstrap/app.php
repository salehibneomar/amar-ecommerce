<?php

use App\Http\Middleware\Access\AppOwnerMiddleware;
use App\Http\Middleware\Access\BackofficeMiddleware;
use App\Http\Middleware\Access\CustomerMiddleware;
use App\Http\Middleware\Auth\GuestMiddleware;
use App\Http\Middleware\Auth\NotBlockedMiddleware;
use App\Http\Middleware\Auth\PasswordResetThrottleMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append([AppOwnerMiddleware::class]);
        $middleware->preventRequestsDuringMaintenance(except: [
            'logout',
            'user/confirm-password',
            'user/confirmed-password-status',
            'email/*',
            'livewire-*',
            'flux/*',
        ]);
        $middleware->alias([
            'backoffice' => BackofficeMiddleware::class,
            'customer' => CustomerMiddleware::class,
            'password-reset-throttle' => PasswordResetThrottleMiddleware::class,
            'not-blocked' => NotBlockedMiddleware::class,
            'guest' => GuestMiddleware::class,
        ]);
        $middleware->redirectGuestsTo(
            fn (Request $request): string => $request->is(adminRoutePrefix().'/*')
                ? route('login')
                : route('user.login'),
        );
        $middleware->redirectUsersTo(
            fn (Request $request): string => $request->user()?->isBackofficeUser()
                ? route('dashboard')
                : route('user.dashboard'),
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->respond(function (Response $response, Throwable $exception, Request $request): Response|RedirectResponse {
            if ($response->getStatusCode() === 403 && $request->user()?->isBackofficeUser() && str_contains((string) $response->headers->get('Content-Type'), 'text/html')) {
                return redirect()->route('dashboard');
            }

            return $response;
        });
    })->create();
