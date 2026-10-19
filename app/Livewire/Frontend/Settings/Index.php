<?php

namespace App\Livewire\Frontend\Settings;

use App\Concerns\PasswordValidationRules;
use App\Services\Frontend\Account\AccountService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Index extends Component
{
    use PasswordValidationRules;

    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    protected AccountService $accountService;

    public function boot(AccountService $accountService): void
    {
        $this->accountService = $accountService;
    }

    #[Computed]
    public function hasPassword(): bool
    {
        return Auth::user()->hasPassword();
    }

    public function updatePassword(): void
    {
        $hadPassword = $this->hasPassword();

        try {
            $validated = $this->validate([
                'current_password' => $hadPassword ? $this->currentPasswordRules() : [],
                'password' => $this->passwordRules(),
            ]);
        } catch (ValidationException $exception) {
            $this->reset('current_password', 'password', 'password_confirmation');

            throw $exception;
        }

        $this->accountService->updatePassword(Auth::user(), $validated['password']);

        $this->reset('current_password', 'password', 'password_confirmation');
        unset($this->hasPassword);

        $this->dispatch(
            'toast-show',
            slots: ['text' => $hadPassword ? __('Password updated.') : __('Password set. You can now also log in with your email and password.')],
            dataset: ['variant' => 'success'],
        );
    }

    public function render(): View
    {
        return view('components.frontend.settings.index');
    }
}
