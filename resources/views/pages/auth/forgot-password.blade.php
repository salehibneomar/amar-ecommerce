<x-layouts::auth :title="__('Forgot password')">
    <div class="flex flex-col gap-6">
        <x-auth.header :title="__('Forgot password')" :description="__('Enter your email to receive a password reset link')" />

        <form method="POST" action="{{ route('password.email') }}">
            @csrf

            <x-auth.resend-countdown :seconds="$resendAvailableIn ?? 0">
                <!-- Email Address -->
                <flux:input
                    name="email"
                    :label="__('Email Address')"
                    type="email"
                    required
                    autofocus
                    placeholder="email@example.com"
                />

                <x-ui.button variant="primary" type="submit" class="w-full" data-test="email-password-reset-link-button">
                    {{ __('Email Password Reset Link') }}
                </x-ui.button>
            </x-auth.resend-countdown>
        </form>

        <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-400">
            <span>{{ __('Or, return to') }}</span>
            <flux:link :href="route('login')" wire:navigate>{{ __('log in') }}</flux:link>
        </div>
    </div>
</x-layouts::auth>
