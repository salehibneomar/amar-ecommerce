<section class="w-full">
    <x-backoffice.layout.main-content-container :title="__('Appearance')" :subtitle="__('Update the appearance settings for your account')" :breadcrumbs="[['label' => __('Settings'), 'href' => route('settings.index')], ['label' => __('Appearance')]]">
        <div class="space-y-6">
            <flux:card class="space-y-4">
                <div>
                    <flux:heading>{{ __('Mode') }}</flux:heading>
                    <flux:text class="mt-1">{{ __('Only you see this choice.') }}</flux:text>
                </div>

                <flux:radio.group x-data variant="segmented" x-model="$flux.appearance">
                    <flux:radio value="light" icon="sun">{{ __('Light') }}</flux:radio>
                    <flux:radio value="dark" icon="moon">{{ __('Dark') }}</flux:radio>
                    <flux:radio value="system" icon="computer-desktop">{{ __('System') }}</flux:radio>
                </flux:radio.group>
            </flux:card>

            @can(\App\Enums\User\PermissionEnum::ManageTheme->value)
                <flux:card>
                    <form wire:submit="saveTheme" class="space-y-5">
                        <div>
                            <flux:heading>{{ __('Brand Color') }}</flux:heading>
                            <flux:text class="mt-1">{{ __('Used by the shop, the backoffice and emails. Every visitor sees the change.') }}</flux:text>
                        </div>

                        <div role="radiogroup" aria-label="{{ __('Brand Color') }}" class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                            @foreach ($this->themeOptions as $value => $option)
                                <x-backoffice.settings.theme-option :value="$value" :label="$option['label']" :theme="$option['theme']" model="themeColor" wire:key="theme-{{ $value }}" />
                            @endforeach
                        </div>

                        <flux:error name="themeColor" />

                        <x-form.actions>
                            <x-ui.button type="submit" variant="primary" data-test="save-theme-button">{{ __('Save') }}</x-ui.button>
                        </x-form.actions>
                    </form>
                </flux:card>
            @endcan
        </div>
    </x-backoffice.layout.main-content-container>
</section>
