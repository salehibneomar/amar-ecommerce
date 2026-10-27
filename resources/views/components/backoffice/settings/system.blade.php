<section class="w-full">
    <x-backoffice.layout.main-content-container :title="__('System')" :subtitle="__('Maintenance mode and application cache')" :breadcrumbs="[['label' => __('Settings'), 'href' => route('settings.index')], ['label' => __('System')]]">
        <flux:card class="space-y-8">
            <div class="grid grid-cols-1 divide-y divide-line lg:grid-cols-2 lg:divide-x lg:divide-y-0">
                <form wire:submit="saveMaintenance" class="space-y-5 pb-8 lg:pe-8 lg:pb-0">
                    <div>
                        <div class="flex items-center gap-2">
                            <flux:heading>{{ __('Maintenance mode') }}</flux:heading>
                            <x-ui.badge :color="$this->maintenanceStatus->color()" data-test="maintenance-status">{{ $this->maintenanceStatus->label() }}</x-ui.badge>
                        </div>
                        <flux:text class="mt-1">{{ __('Every public and customer page shows a friendly maintenance page during the window, while the admin panel stays open for admins. Leave the times empty to start now and end manually.') }}</flux:text>
                    </div>

                    <x-backoffice.form.toggle-field wire:model.live="maintenanceEnabled" :label="__('Enable Maintenance Mode')" />

                    @if ($maintenanceEnabled)
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <flux:input wire:model="maintenanceStartsAt" type="datetime-local" :label="__('Starts at')" :description="__('Empty starts now')" />
                            <flux:input wire:model="maintenanceEndsAt" type="datetime-local" :label="__('Ends at')" :description="__('Empty means until turned off')" />
                        </div>
                    @endif

                    @if ($this->bypassUrl)
                        <flux:input :value="$this->bypassUrl" readonly copyable :label="__('Bypass Link')" :description="__('Opens the public site in this browser while maintenance is active.')" data-test="bypass-link" />
                    @endif

                    <x-form.actions>
                        <x-ui.button type="submit" variant="primary" data-test="save-maintenance-button">{{ __('Save') }}</x-ui.button>
                    </x-form.actions>
                </form>

                <div class="space-y-5 pt-8 lg:ps-8 lg:pt-0">
                    <div>
                        <flux:heading>{{ __('Application cache') }}</flux:heading>
                        <flux:text class="mt-1">{{ __('Clear cached config, routes and views, or rebuild them for faster responses') }}</flux:text>
                    </div>

                    <div class="flex flex-wrap items-center gap-4">
                        <x-ui.button wire:click="clearCache" wire:loading.attr="disabled" data-test="clear-cache-button">
                            {{ __('Clear Cache') }}
                        </x-ui.button>
                        <x-ui.button variant="primary" wire:click="optimize" wire:loading.attr="disabled" data-test="optimize-button">
                            {{ __('Optimize') }}
                        </x-ui.button>
                    </div>
                </div>
            </div>
        </flux:card>
    </x-backoffice.layout.main-content-container>
</section>
