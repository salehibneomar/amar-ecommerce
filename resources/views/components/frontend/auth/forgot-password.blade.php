<div class="flex flex-col gap-6">
    <x-auth.header :title="__('Forgot password')" :description="__('Enter your email to receive a password reset link')" />

    <form wire:submit="sendResetLink">
        <x-auth.resend-countdown :seconds="$this->resendAvailableIn" wire:key="resend-countdown-{{ $this->resendAvailableIn }}">
            <flux:input wire:model="email" :label="__('Email Address')" type="email" required autofocus
                autocomplete="email" placeholder="email@example.com" />

            <x-ui.button variant="primary" type="submit" class="w-full" data-test="email-password-reset-link-button">
                {{ __('Email Password Reset Link') }}
            </x-ui.button>
        </x-auth.resend-countdown>
    </form>

    <x-frontend.layout.auth-link :prompt="__('Remembered it?')" :href="route('user.login')">{{ __('Log in') }}</x-frontend.layout.auth-link>
</div>
