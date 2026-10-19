<x-layouts::frontend.auth :title="__('Verify email')">
    <div class="mt-4 flex flex-col gap-6">
        <flux:text class="text-center">
            {{ __('Please verify your email address by clicking on the link we emailed to you. The link works for :minutes minutes.', ['minutes' => emailVerificationExpireMinutes()]) }}
        </flux:text>

        <div class="flex flex-col items-center justify-between space-y-3">
            <x-auth.resend-countdown :seconds="$resendAvailableIn ?? 0" :message="__('You can resend the email in')" class="w-full">
                <form method="POST" action="{{ route('verification.send') }}">
                    @csrf
                    <flux:button type="submit" variant="primary" class="w-full">
                        {{ __('Resend verification email') }}
                    </flux:button>
                </form>
            </x-auth.resend-countdown>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <flux:button variant="ghost" type="submit" class="text-sm cursor-pointer" data-test="logout-button">
                    {{ __('Log out') }}
                </flux:button>
            </form>
        </div>
    </div>
</x-layouts::frontend.auth>
