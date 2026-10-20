<?php

use App\Concerns\ProfileValidationRules;
use App\Livewire\Concerns\InteractsWithAddresses;
use App\Livewire\Concerns\InteractsWithAvatar;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Profile')] class extends Component {
    use InteractsWithAddresses, InteractsWithAvatar, ProfileValidationRules;

    public string $name = '';
    public string $email = '';
    public string $phone = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
        $this->phone = (string) Auth::user()->phone;
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate([...$this->profileRules($user->id), 'phone' => $this->phoneRules($user->id)]);

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        Flux::toast(variant: 'success', text: __('Profile updated.'));
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }

    #[Computed]
    public function hasUnverifiedEmail(): bool
    {
        return Auth::user() instanceof MustVerifyEmail && ! Auth::user()->hasVerifiedEmail();
    }

}; ?>

<section class="w-full">
    <flux:heading level="2" class="sr-only">{{ __('Profile settings') }}</flux:heading>

    <x-pages::settings.layout :heading="__('Profile')" :subheading="__('Update your name, email address, mobile number and addresses')">
        <form wire:submit="updateProfileInformation" class="w-full space-y-6">
            <x-media.avatar-upload :name="$name" :url="$this->avatarUrl" :accept="mediaAccept('user.media.avatar')" />
            <flux:input wire:model="name" :label="__('Name')" type="text" required autofocus autocomplete="name" />

            <div>
                <flux:input wire:model="email" :label="__('Email')" type="email" required autocomplete="email" />

                @if ($this->hasUnverifiedEmail)
                    <div>
                        <flux:text class="mt-4">
                            {{ __('Your email address is unverified.') }}

                            <flux:link class="text-sm cursor-pointer" wire:click.prevent="resendVerificationNotification">
                                {{ __('Click here to re-send the verification email.') }}
                            </flux:link>
                        </flux:text>

                        @if (session('status') === 'verification-link-sent')
                            <flux:text class="mt-2 font-medium !dark:text-green-400 !text-green-600">
                                {{ __('A new verification link has been sent to your email address.') }}
                            </flux:text>
                        @endif
                    </div>
                @endif
            </div>

            <flux:input wire:model="phone" :label="__('Mobile Number')" type="tel" inputmode="numeric" :maxlength="validPhoneLength()" placeholder="01XXXXXXXXX" autocomplete="tel" />

            <x-form.actions>
                <x-ui.button variant="primary" type="submit" data-test="update-profile-button">{{ __('Save') }}</x-ui.button>
            </x-form.actions>
        </form>

        <flux:separator class="my-8" />

        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Addresses') }}</flux:heading>
                <flux:subheading>{{ __('Your shipping and billing addresses.') }}</flux:subheading>
            </div>
            <x-form.address-form />
        </div>
    </x-pages::settings.layout>
</section>
