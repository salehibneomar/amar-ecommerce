<?php

namespace App\Livewire\Frontend\Profile;

use App\Concerns\ProfileValidationRules;
use App\Enums\User\SocialProviderEnum;
use App\Livewire\Concerns\InteractsWithAddresses;
use App\Livewire\Concerns\InteractsWithAvatar;
use App\Services\Frontend\Account\AccountService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Index extends Component
{
    use InteractsWithAddresses, InteractsWithAvatar, ProfileValidationRules;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    protected AccountService $accountService;

    public function boot(AccountService $accountService): void
    {
        $this->accountService = $accountService;
    }

    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
        $this->phone = (string) Auth::user()->phone;
    }

    public function updateProfile(): void
    {
        $validated = $this->validate([...$this->profileRules(Auth::user()->id), 'phone' => $this->phoneRules(Auth::user()->id)]);

        $this->accountService->updateProfile(Auth::user(), $validated);

        $this->dispatch(
            'toast-show',
            slots: ['text' => __('Profile updated successfully.')],
            dataset: ['variant' => 'success'],
        );
    }

    /**
     * @return list<array{provider: string, label: string, connected: bool}>
     */
    #[Computed]
    public function socialAccounts(): array
    {
        return array_map(fn (SocialProviderEnum $provider): array => [
            'provider' => $provider->value,
            'label' => $provider->label(),
            'connected' => filled(Auth::user()->{$provider->column()}),
        ], SocialProviderEnum::cases());
    }

    public function render(): View
    {
        return view('components.frontend.profile.index');
    }
}
