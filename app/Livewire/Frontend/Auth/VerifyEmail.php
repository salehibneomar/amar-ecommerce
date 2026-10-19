<?php

namespace App\Livewire\Frontend\Auth;

use App\Services\Auth\EmailVerificationService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class VerifyEmail extends Component
{
    public string $email = '';

    protected EmailVerificationService $emailVerificationService;

    public function boot(EmailVerificationService $emailVerificationService): void
    {
        $this->emailVerificationService = $emailVerificationService;
    }

    public function mount(): void
    {
        $user = $this->emailVerificationService->pendingUser();

        if ($user === null) {
            $this->redirectRoute('user.login', navigate: true);

            return;
        }

        $this->email = $user->email;
    }

    public function resend(): void
    {
        if (! $this->emailVerificationService->resend((string) request()->ip())) {
            session()->flash('success', __('Your email is already verified. You can log in now.'));
            $this->redirectRoute('user.login', navigate: true);

            return;
        }

        unset($this->resendAvailableIn, $this->isWaitingForLink);

        $this->dispatch('toast-show', slots: ['text' => __('A new verification link has been sent to your email.')], dataset: ['variant' => 'success']);
    }

    public function checkStatus(): void
    {
        if ($this->emailVerificationService->isResolved()) {
            session()->flash('success', __('Your email is verified. You can log in now.'));
            $this->redirectRoute('home', navigate: true);
        }
    }

    #[Computed]
    public function resendAvailableIn(): int
    {
        return $this->emailVerificationService->cooldownSeconds();
    }

    #[Computed]
    public function isWaitingForLink(): bool
    {
        return $this->emailVerificationService->linkExpiresIn() > 0;
    }

    #[Computed]
    public function expireMinutes(): int
    {
        return emailVerificationExpireMinutes();
    }

    public function render(): View
    {
        return view('components.frontend.auth.verify-email');
    }
}
