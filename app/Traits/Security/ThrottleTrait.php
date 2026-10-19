<?php

namespace App\Traits\Security;

use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

trait ThrottleTrait
{
    private function throttle(string $key, int $maxAttempts, string $field, string $message = 'Too many attempts. Please try again in :seconds seconds.'): void
    {
        if (! RateLimiter::attempt($key, $maxAttempts, fn (): bool => true)) {
            throw ValidationException::withMessages([
                $field => __($message, ['seconds' => RateLimiter::availableIn($key)]),
            ]);
        }
    }
}
