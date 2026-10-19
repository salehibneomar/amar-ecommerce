<?php

namespace App\Http\Responses;

use Illuminate\Http\Request;
use Laravel\Fortify\Http\Responses\PasswordResetResponse;
use Symfony\Component\HttpFoundation\Response;

class PasswordResetCompletedResponse extends PasswordResetResponse
{
    /**
     * @param  Request  $request
     * @return Response
     */
    public function toResponse($request)
    {
        return $request->wantsJson() ? parent::toResponse($request) : redirect()->route('login')->with('success', __($this->status));
    }
}
