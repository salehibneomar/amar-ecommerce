<?php

namespace App\Http\Middleware\Access;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AppOwnerMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $response->headers->set(
            'X-App-Owner',
            config('app.owner', 'Application Owner'),
        );
        $response->headers->set(
            'X-App-Developer',
            config('app.developer', 'Application Developer'),
        );

        return $response;
    }
}
