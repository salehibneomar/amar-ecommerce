<?php

namespace App\Http\Responses;

use App\Services\Auth\EmailVerificationService;
use Illuminate\Http\Request;
use Laravel\Fortify\Http\Responses\EmailVerificationNotificationSentResponse;
use Symfony\Component\HttpFoundation\Response;

class VerificationLinkSentResponse extends EmailVerificationNotificationSentResponse
{
    /**
     * @param  Request  $request
     * @return Response
     */
    public function toResponse($request)
    {
        app(EmailVerificationService::class)->startCooldown();

        return $request->wantsJson() ? parent::toResponse($request) : back()->with('success', __('A new verification link has been sent to your email.'));
    }
}
