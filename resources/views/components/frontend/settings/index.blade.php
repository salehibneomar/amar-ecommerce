<x-frontend.layout.page :title="__('Settings')" :subtitle="__('Manage your password and appearance.')">
    <x-frontend.cards.card
        :title="$this->hasPassword ? __('Update password') : __('Set password')"
        :description="$this->hasPassword
            ? __('Use a long, random password to keep your account secure.')
            : __('You signed up with a social account. Set a password to also log in with your email address.')">

        <form wire:submit="updatePassword" class="space-y-6">
            @if ($this->hasPassword)
                <flux:input wire:model="current_password" :label="__('Current password')" type="password" required
                    autocomplete="current-password" viewable />
            @endif
            <flux:input wire:model="password" :label="__('New password')" type="password" required
                autocomplete="new-password" viewable />
            <flux:input wire:model="password_confirmation" :label="__('Confirm password')" type="password" required
                autocomplete="new-password" viewable />

            <div class="flex justify-end">
                <flux:button variant="primary" type="submit" data-test="update-password-button">{{ __('Save') }}</flux:button>
            </div>
        </form>
    </x-frontend.cards.card>

    <x-frontend.cards.card :title="__('Appearance')" :description="__('Choose how the app looks on this device.')">
        <flux:radio.group x-data variant="segmented" x-model="$flux.appearance">
            <flux:radio value="light" icon="sun">{{ __('Light') }}</flux:radio>
            <flux:radio value="dark" icon="moon">{{ __('Dark') }}</flux:radio>
            <flux:radio value="system" icon="computer-desktop">{{ __('System') }}</flux:radio>
        </flux:radio.group>
    </x-frontend.cards.card>
</x-frontend.layout.page>
