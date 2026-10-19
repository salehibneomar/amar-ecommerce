<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Services\Frontend\Shop\OrderService;
use App\Traits\Security\ThrottleTrait;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Auth;

class EmailVerificationService
{
    use ThrottleTrait;

    private const COOLDOWN_SESSION_KEY = 'email_verification.available_at';

    private const PENDING_USER_SESSION_KEY = 'email_verification.user_id';

    private const SENT_AT_SESSION_KEY = 'email_verification.sent_at';

    public function __construct(private readonly OrderService $orderService) {}

    public function startCooldown(): void
    {
        session()->put(self::COOLDOWN_SESSION_KEY, now()->addSeconds(emailVerificationResendSeconds())->getTimestamp());
        session()->put(self::SENT_AT_SESSION_KEY, now()->getTimestamp());
    }

    public function cooldownSeconds(): int
    {
        return max(0, (int) session(self::COOLDOWN_SESSION_KEY, 0) - now()->getTimestamp());
    }

    public function linkExpiresIn(): int
    {
        return max(0, (int) session(self::SENT_AT_SESSION_KEY, 0) + emailVerificationExpireMinutes() * 60 - now()->getTimestamp());
    }

    public function awaitVerification(User $user): void
    {
        session()->put(self::PENDING_USER_SESSION_KEY, $user->getKey());
        $this->startCooldown();
    }

    public function send(User $user): void
    {
        $user->sendEmailVerificationNotification();
        $this->awaitVerification($user);
    }

    public function pendingUser(): ?User
    {
        return User::query()
            ->whereKey((int) session(self::PENDING_USER_SESSION_KEY))
            ->whereNull('email_verified_at')
            ->first();
    }

    public function isResolved(): bool
    {
        return Auth::check() || $this->pendingUser() === null;
    }

    public function resend(string $ip): bool
    {
        $user = $this->pendingUser();

        if ($user === null) {
            return false;
        }

        if ($this->cooldownSeconds() === 0) {
            $this->throttle("verification-email:{$ip}", throttleAttempts('verification_email'), 'email');
            $this->send($user);
        }

        return true;
    }

    public function verify(int $userId, string $hash): ?User
    {
        $user = User::find($userId);

        if ($user === null || ! hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            return null;
        }

        if ($user->hasVerifiedEmail()) {
            return $user->email_verified_at->gt(now()->subMinutes(emailVerificationExpireMinutes())) ? $user : null;
        }

        $user->markEmailAsVerified();
        event(new Verified($user));
        $this->orderService->claimGuestOrders($user);

        return $user;
    }
}
