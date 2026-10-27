<section class="w-full">
    <x-backoffice.layout.main-content-container :title="__('Analytics')" :subtitle="__('Track visits and shop events like product views, cart changes, checkout and purchases on the storefront. The admin panel is never tracked.')" :breadcrumbs="[['label' => __('Settings'), 'href' => route('settings.index')], ['label' => __('Analytics')]]">
        <div class="space-y-6">
            @foreach ($this->providers as $provider)
                <flux:card wire:key="analytics-{{ $provider->value }}">
                    <form wire:submit="save('{{ $provider->value }}')" class="space-y-5">
                        <div>
                            <flux:heading>{{ $provider->label() }}</flux:heading>
                            <flux:text class="mt-1">{{ $provider->description() }}</flux:text>
                        </div>

                        <x-backoffice.form.toggle-field wire:model="trackers.{{ $provider->value }}.is_enabled" :label="__('Enable :provider', ['provider' => $provider->label()])" />

                        <flux:input wire:model="trackers.{{ $provider->value }}.tracking_id" :label="__('Tracking ID')" :placeholder="$provider->placeholder()" />

                        <x-form.actions>
                            <x-ui.button type="submit" variant="primary" data-test="save-{{ $provider->value }}-button">{{ __('Save') }}</x-ui.button>
                        </x-form.actions>
                    </form>
                </flux:card>
            @endforeach
        </div>
    </x-backoffice.layout.main-content-container>
</section>
