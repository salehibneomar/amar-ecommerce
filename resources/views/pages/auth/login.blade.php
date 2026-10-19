<x-layouts::auth :title="__('Log in')">
    <div class="flex flex-col gap-6">
        <x-auth.header :title="__('Admin log in')" :description="__('Sign in to manage the back office')" />

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-6">
            @csrf

            <!-- Email Address -->
            <flux:input
                name="email"
                :label="__('Email Address')"
                :value="old('email')"
                type="email"
                required
                autofocus
                autocomplete="email"
                placeholder="email@example.com"
            />

            <!-- Password -->
            <div class="relative">
                <flux:input
                    name="password"
                    :label="__('Password')"
                    type="password"
                    required
                    autocomplete="current-password"
                    :placeholder="__('Password')"
                    viewable
                />
            </div>

            <!-- Remember Me -->
            <div class="flex items-center justify-between gap-4">
                <flux:checkbox name="remember" :label="__('Remember Me')" :checked="old('remember')" />

                @if (Route::has('password.request'))
                    <flux:link class="shrink-0 text-sm" :href="route('password.request')" wire:navigate>
                        {{ __('Forgot your password?') }}
                    </flux:link>
                @endif
            </div>

            <div class="flex items-center justify-end">
                <x-ui.button variant="primary" type="submit" class="w-full" data-test="login-button">
                    {{ __('Log in') }}
                </x-ui.button>
            </div>
        </form>

    </div>
</x-layouts::auth>
