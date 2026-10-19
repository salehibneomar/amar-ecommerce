<?php

namespace App\Livewire\Frontend\Auth;

use App\Enums\User\SocialProviderEnum;
use App\Services\Frontend\Account\AccountService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Login extends Component
{
    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    protected AccountService $accountService;

    public function boot(AccountService $accountService): void
    {
        $this->accountService = $accountService;
    }

    public function login(): void
    {
        $this->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $isLoggedIn = $this->accountService->login(
            $this->email,
            $this->password,
            $this->remember,
            Str::transliterate(Str::lower($this->email).'|'.request()->ip()),
        );

        if (! $isLoggedIn) {
            $this->redirectRoute('user.verification.notice', navigate: true);

            return;
        }

        session()->regenerate();

        $this->redirectIntended(route('user.dashboard'));
    }

    /**
     * @return list<SocialProviderEnum>
     */
    #[Computed]
    public function socialProviders(): array
    {
        return SocialProviderEnum::cases();
    }

    public function render(): View
    {
        return view('components.frontend.auth.login');
    }
}
