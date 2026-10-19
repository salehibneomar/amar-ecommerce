<?php

namespace App\Http\Responses;

use App\Services\Auth\PasswordResetService;
use Illuminate\Http\Request;
use Laravel\Fortify\Http\Responses\SuccessfulPasswordResetLinkRequestResponse;
use Symfony\Component\HttpFoundation\Response;

class PasswordResetLinkSentResponse extends SuccessfulPasswordResetLinkRequestResponse
{
    /**
     * @param  Request  $request
     * @return Response
     */
    public function toResponse($request)
    {
        app(PasswordResetService::class)->startCooldown(config('fortify.passwords'));

        return $request->wantsJson() ? parent::toResponse($request) : back()->with('success', __($this->status));
    }
}
