<?php

namespace App\Http\Responses;

use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\VerifyEmailResponse;
use Symfony\Component\HttpFoundation\Response;

class EmailVerifiedResponse implements VerifyEmailResponse
{
    /**
     * @param  Request  $request
     * @return Response
     */
    public function toResponse($request)
    {
        $route = $request->user()?->isBackofficeUser() ? 'dashboard' : 'user.dashboard';

        return redirect()->intended(route($route).'?verified=1')->with('success', __('Your email is verified.'));
    }
}
