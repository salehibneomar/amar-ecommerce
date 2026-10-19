<div class="flex flex-col gap-6">
    <x-auth.header :title="__('Create an account')" :description="__('Build your library of farming books')" />

    <form wire:submit="register" class="flex flex-col gap-6">
        <flux:input wire:model="name" :label="__('Name')" type="text" required autofocus autocomplete="name"
            :placeholder="__('Full name')" />

        <flux:input wire:model="email" :label="__('Email address')" type="email" required autocomplete="email"
            placeholder="email@example.com" />

        <flux:input wire:model="password" :label="__('Password')" type="password" required
            autocomplete="new-password" :placeholder="__('Password')" viewable />

        <flux:input wire:model="password_confirmation" :label="__('Confirm password')" type="password" required
            autocomplete="new-password" :placeholder="__('Confirm password')" viewable />

        <flux:button type="submit" variant="primary" class="w-full" data-test="register-user-button">
            {{ __('Create account') }}
        </flux:button>
    </form>

    <x-auth.social-buttons :providers="$this->socialProviders" />

    <x-frontend.layout.auth-link :prompt="__('Already have an account?')" :href="route('user.login')">{{ __('Log in') }}</x-frontend.layout.auth-link>
</div>
