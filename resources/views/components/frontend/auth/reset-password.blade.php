<div class="flex flex-col gap-6">
    <x-auth.header :title="__('Reset password')" :description="__('Please enter your new password below')" />

    <form wire:submit="resetPassword" class="flex flex-col gap-6">
        <flux:input wire:model="email" :label="__('Email address')" type="email" required autocomplete="email" />

        <flux:input wire:model="password" :label="__('Password')" type="password" required
            autocomplete="new-password" :placeholder="__('Password')" viewable />

        <flux:input wire:model="password_confirmation" :label="__('Confirm password')" type="password" required
            autocomplete="new-password" :placeholder="__('Confirm password')" viewable />

        <flux:button type="submit" variant="primary" class="w-full" data-test="reset-password-button">
            {{ __('Reset password') }}
        </flux:button>
    </form>

    <x-frontend.layout.auth-link :prompt="__('Back to')" :href="route('user.login')">{{ __('Log in') }}</x-frontend.layout.auth-link>
</div>
