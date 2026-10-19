<?php

namespace App\Livewire\Frontend\Auth;

use App\Enums\User\SocialProviderEnum;
use App\Services\Frontend\Account\AccountService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Register extends Component
{
    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    protected AccountService $accountService;

    public function boot(AccountService $accountService): void
    {
        $this->accountService = $accountService;
    }

    public function register(): void
    {
        $this->accountService->register([
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
            'password_confirmation' => $this->password_confirmation,
        ], (string) request()->ip());

        $this->redirectRoute('user.verification.notice', navigate: true);
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
        return view('components.frontend.auth.register');
    }
}
