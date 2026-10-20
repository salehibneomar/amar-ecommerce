<div class="flex flex-col gap-6">
    <x-auth.header :title="__('Welcome back')" :description="__('Log in to track your orders')" />

    <form wire:submit="login" class="flex flex-col gap-6">
        <flux:input wire:model="email" :label="__('Email Address')" type="email" required autofocus
            autocomplete="email" placeholder="email@example.com" />

        <flux:input wire:model="password" :label="__('Password')" type="password" required
            autocomplete="current-password" :placeholder="__('Password')" viewable />

        <div class="flex items-center justify-between gap-4">
            <flux:checkbox wire:model="remember" :label="__('Remember Me')" />
            <flux:link class="shrink-0 text-sm" :href="route('user.password.request')" wire:navigate>
                {{ __('Forgot your password?') }}
            </flux:link>
        </div>

        <x-ui.button variant="primary" type="submit" class="w-full" data-test="login-button">
            {{ __('Log in') }}
        </x-ui.button>
    </form>

    <x-auth.social-buttons :providers="$this->socialProviders" />

    <x-frontend.layout.auth-link :prompt="__('Don\'t have an account?')" :href="route('user.register')">{{ __('Sign up') }}</x-frontend.layout.auth-link>
</div>
