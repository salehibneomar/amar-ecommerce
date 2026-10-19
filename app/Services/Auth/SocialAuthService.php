<?php

namespace App\Services\Auth;

use App\Enums\User\SocialProviderEnum;
use App\Enums\User\UserRoleEnum;
use App\Enums\User\UserStatusEnum;
use App\Models\User;
use App\Services\Frontend\Shop\OrderService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Contracts\User as SocialiteUser;

class SocialAuthService
{
    public function __construct(private readonly OrderService $orderService) {}

    public function resolveUser(SocialProviderEnum $provider, SocialiteUser $socialUser): User
    {
        $email = $socialUser->getEmail();

        if (blank($email)) {
            throw ValidationException::withMessages([
                'email' => __('Your :provider account did not share an email address.', ['provider' => $provider->label()]),
            ]);
        }

        $user = User::query()->where($provider->column(), $socialUser->getId())->first()
            ?? User::query()->where('email', $email)->first();

        if ($user?->isBackofficeUser()) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        if ($user?->isBlocked()) {
            throw ValidationException::withMessages([
                'email' => __('Your account has been blocked.'),
            ]);
        }

        if ($user) {
            $user->forceFill([
                $provider->column() => $socialUser->getId(),
                'email_verified_at' => $user->email_verified_at ?? now(),
            ])->save();
            $this->orderService->claimGuestOrders($user);

            return $user;
        }

        return DB::transaction(function () use ($provider, $socialUser, $email): User {
            $user = User::create([
                'name' => $socialUser->getName() ?: $email,
                'email' => $email,
                'status' => UserStatusEnum::Active,
                $provider->column() => $socialUser->getId(),
            ]);

            $user->forceFill(['email_verified_at' => now()])->save();
            $user->assignRole(UserRoleEnum::User->value);
            $this->orderService->claimGuestOrders($user);

            return $user;
        });
    }
}
