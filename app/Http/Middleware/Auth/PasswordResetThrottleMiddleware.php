<?php

namespace App\Http\Middleware\Auth;

use App\Services\Auth\PasswordResetService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PasswordResetThrottleMiddleware
{
    public function __construct(private readonly PasswordResetService $passwordResetService) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('post') && $request->routeIs('password.email')) {
            $this->passwordResetService->ensureNotThrottled((string) $request->ip());
        }

        return $next($request);
    }
}
