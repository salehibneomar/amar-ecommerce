<div class="flex flex-col gap-6" x-on:visibilitychange.document="document.visibilityState === 'visible' && $wire.checkStatus()">
    @if ($this->isWaitingForLink)
        <div wire:poll.10s="checkStatus" class="hidden" data-test="verification-poll"></div>
    @endif

    <x-auth.header :title="__('Verify your email')" :description="__('We sent a verification link to :email.', ['email' => $email])" />

    <flux:text class="text-center">
        {{ __('Open the link to verify your email, then log in. The link works for :minutes minutes.', ['minutes' => $this->expireMinutes]) }}
    </flux:text>

    <form wire:submit="resend">
        <x-auth.resend-countdown :seconds="$this->resendAvailableIn" :message="__('You can resend the email in')" wire:key="resend-countdown-{{ $this->resendAvailableIn }}">
            <flux:button variant="primary" type="submit" class="w-full" data-test="resend-verification-button">
                {{ __('Resend verification email') }}
            </flux:button>
        </x-auth.resend-countdown>
    </form>

    <x-frontend.layout.auth-link :prompt="__('Already verified?')" :href="route('user.login')">{{ __('Log in') }}</x-frontend.layout.auth-link>
</div>
