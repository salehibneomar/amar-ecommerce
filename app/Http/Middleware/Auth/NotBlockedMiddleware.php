<?php

namespace App\Http\Middleware\Auth;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NotBlockedMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->isBlocked()) {
            $loginRoute = $request->user()->isBackofficeUser() ? 'login' : 'user.login';

            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route($loginRoute)
                ->withErrors([
                    'email' => 'Your account has been blocked.',
                ]);
        }

        return $next($request);
    }
}
