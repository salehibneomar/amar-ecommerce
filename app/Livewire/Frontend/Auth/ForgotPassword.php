<?php

namespace App\Livewire\Frontend\Auth;

use App\Services\Auth\PasswordResetService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class ForgotPassword extends Component
{
    private const PASSWORD_BROKER = 'users';

    public string $email = '';

    protected PasswordResetService $passwordResetService;

    public function boot(PasswordResetService $passwordResetService): void
    {
        $this->passwordResetService = $passwordResetService;
    }

    public function sendResetLink(): void
    {
        $this->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        $status = $this->passwordResetService->sendResetLink(self::PASSWORD_BROKER, $this->email, (string) request()->ip());

        $this->reset('email');
        unset($this->resendAvailableIn);

        $this->dispatch('toast-show', slots: ['text' => __($status)], dataset: ['variant' => 'success']);
    }

    #[Computed]
    public function resendAvailableIn(): int
    {
        return $this->passwordResetService->cooldownSeconds(self::PASSWORD_BROKER);
    }

    public function render(): View
    {
        return view('components.frontend.auth.forgot-password');
    }
}
