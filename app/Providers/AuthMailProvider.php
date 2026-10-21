<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AuthMailProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->configurePasswordResetUrl();
        $this->configureEmailVerification();
    }

    private function configurePasswordResetUrl(): void
    {
        ResetPassword::createUrlUsing(static fn (User $user, string $token): string => route(
            $user->isBackofficeUser() ? 'password.reset' : 'user.password.reset',
            ['token' => $token, 'email' => $user->getEmailForPasswordReset()],
        ));
    }

    private function configureEmailVerification(): void
    {
        VerifyEmail::createUrlUsing(static fn (User $user): string => URL::temporarySignedRoute(
            'user.verification.verify',
            now()->addMinutes(emailVerificationExpireMinutes()),
            ['id' => $user->getKey(), 'hash' => sha1($user->getEmailForVerification())],
        ));

        VerifyEmail::toMailUsing(static fn (User $user, string $url): MailMessage => (new MailMessage)
            ->subject(__('Verify your email address'))
            ->line(__('Please click the button below to verify your email address.'))
            ->action(__('Verify email address'), $url)
            ->line(__('This link works for :minutes minutes. If it expires, log in again to get a new one.', ['minutes' => emailVerificationExpireMinutes()]))
            ->line(__('If you did not create an account, no further action is required.')));
    }
}
