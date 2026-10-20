<?php

namespace App\Services\Frontend\Account;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Models\User;
use App\Services\Auth\EmailVerificationService;
use App\Services\Frontend\Shop\OrderService;
use App\Traits\Security\ThrottleTrait;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class AccountService
{
    use ThrottleTrait;

    public function __construct(
        private readonly CreateNewUser $createNewUser,
        private readonly ResetUserPassword $resetUserPassword,
        private readonly EmailVerificationService $emailVerificationService,
        private readonly OrderService $orderService,
    ) {}

    public function login(string $email, string $password, bool $remember, string $throttleKey): bool
    {
        $this->throttle("login:{$throttleKey}", throttleAttempts('login'), 'email', 'auth.throttle');

        $user = Auth::getProvider()->retrieveByCredentials(['email' => $email]);

        if (! $user instanceof User || $user->isBackofficeUser() || ! Auth::getProvider()->validateCredentials($user, ['password' => $password])) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        if ($user->isBlocked()) {
            throw ValidationException::withMessages([
                'email' => __('Your account has been blocked.'),
            ]);
        }

        if (! $user->hasVerifiedEmail()) {
            $this->emailVerificationService->send($user);

            return false;
        }

        Auth::login($user, $remember);
        $this->orderService->claimGuestOrders($user);

        return true;
    }

    /**
     * @param  array<string, string>  $input
     */
    public function register(array $input, string $throttleKey): User
    {
        $this->throttle("register:{$throttleKey}", throttleAttempts('register'), 'email');

        $user = $this->createNewUser->create($input);

        event(new Registered($user));

        $this->emailVerificationService->awaitVerification($user);

        return $user;
    }

    /**
     * @param  array{token: string, email: string, password: string, password_confirmation: string}  $input
     */
    public function resetPassword(array $input): string
    {
        $status = Password::reset($input, fn (User $user) => $this->resetUserPassword->reset($user, $input));

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => __($status),
            ]);
        }

        return $status;
    }

    /**
     * @param  array{name: string, email: string}  $data
     */
    public function updateProfile(User $user, array $data): bool
    {
        return $user->update($data);
    }

    /**
     * @param  array{shipping_address: string|null, billing_address: string|null}  $data
     */
    public function updateAddresses(User $user, array $data): bool
    {
        return $user->update($data);
    }

    public function updateAvatar(User $user, ?UploadedFile $avatar): void
    {
        $user->syncMedia('user.media.avatar', $avatar, remove: $avatar === null);
        $user->load('media');
    }

    public function updatePassword(User $user, string $password): bool
    {
        return $user->update(['password' => $password]);
    }
}
