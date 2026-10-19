<?php

namespace App\Services\Auth;

use App\Traits\Security\ThrottleTrait;
use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class PasswordResetService
{
    use ThrottleTrait;

    private const COOLDOWN_SESSION_KEY = 'password_reset.available_at';

    public function ensureNotThrottled(string $ip): void
    {
        $this->throttle("password-reset:{$ip}", throttleAttempts('password_reset'), 'email');
    }

    public function sendResetLink(string $broker, string $email, string $ip): string
    {
        $this->ensureNotThrottled($ip);

        $status = Password::broker($broker)->sendResetLink(['email' => $email]);

        if ($status !== Password::RESET_LINK_SENT) {
            throw ValidationException::withMessages([
                'email' => __($status),
            ]);
        }

        $this->startCooldown($broker);

        return $status;
    }

    public function ensureValidResetLink(string $broker, string $email, string $token, string $ip): void
    {
        $this->throttle("password-reset-link:{$ip}", throttleAttempts('password_reset_link'), 'token');

        /** @var PasswordBroker $passwordBroker */
        $passwordBroker = Password::broker($broker);
        $user = $email === '' ? null : $passwordBroker->getUser(['email' => $email]);

        if ($user === null || ! $passwordBroker->tokenExists($user, $token)) {
            throw ValidationException::withMessages([
                'token' => __('This password reset link has expired or is invalid.'),
            ]);
        }
    }

    public function startCooldown(string $broker): void
    {
        session()->put($this->cooldownKey($broker), now()->addSeconds(passwordResetResendSeconds())->getTimestamp());
    }

    public function cooldownSeconds(string $broker): int
    {
        return max(0, (int) session($this->cooldownKey($broker), 0) - now()->getTimestamp());
    }

    private function cooldownKey(string $broker): string
    {
        return self::COOLDOWN_SESSION_KEY.".{$broker}";
    }
}
