<section class="w-full">
    <x-backoffice.layout.main-content-container :title="__('Payment Gateways')" :subtitle="__('Manage the payment methods offered at checkout and the order in which customers see them')" :breadcrumbs="[['label' => __('Settings'), 'href' => route('settings.index')], ['label' => __('Payment Gateways')]]">
        <div class="space-y-6">
            <flux:card>
                <form wire:submit="saveGateways" class="space-y-5">
                    <div>
                        <flux:heading>{{ __('Checkout Gateways') }}</flux:heading>
                        <flux:text class="mt-1">{{ __('Enabled gateways appear at checkout in the order listed below. Gateway credentials are managed in the environment file, and any gateway without them is marked as not installed.') }}</flux:text>
                    </div>

                    <div class="divide-y divide-line rounded-lg border border-line">
                        @foreach ($gateways as $index => $row)
                            <x-ui.list-item wire:key="gateway-{{ $row['gateway'] }}" :chevron="false" data-test="gateway-{{ $row['gateway'] }}">
                                <div class="flex flex-wrap items-center gap-2">
                                    <flux:heading>{{ $this->gatewayDetails[$row['gateway']]['label'] }}</flux:heading>
                                    @if (! $this->gatewayDetails[$row['gateway']]['is_configured'])
                                        <x-ui.badge color="amber">{{ __('Not Installed') }}</x-ui.badge>
                                    @elseif ($row['is_enabled'])
                                        <x-ui.badge color="green">{{ __('Enabled') }}</x-ui.badge>
                                    @else
                                        <x-ui.badge>{{ __('Disabled') }}</x-ui.badge>
                                    @endif
                                </div>
                                <flux:text class="mt-1">{{ $this->gatewayDetails[$row['gateway']]['description'] }}</flux:text>
                                <flux:error name="gateways.{{ $index }}.is_enabled" />

                                <x-slot:trailing>
                                    <div class="flex shrink-0 items-center gap-3">
                                        <flux:switch wire:model.live="gateways.{{ $index }}.is_enabled" :aria-label="__('Turn On :gateway', ['gateway' => $this->gatewayDetails[$row['gateway']]['label']])" />
                                        <x-backoffice.management.actions>
                                            <x-backoffice.management.action icon="arrow-up" :label="__('Move Up')" wire:click="move({{ $index }}, -1)" :disabled="$loop->first || ! $this->gatewayDetails[$row['gateway']]['is_configured']" />
                                            <x-backoffice.management.action icon="arrow-down" :label="__('Move Down')" wire:click="move({{ $index }}, 1)" :disabled="$loop->last || ! $this->gatewayDetails[$row['gateway']]['is_configured']" />
                                        </x-backoffice.management.actions>
                                    </div>
                                </x-slot:trailing>
                            </x-ui.list-item>
                        @endforeach
                    </div>

                    <x-form.actions>
                        <x-ui.button type="submit" variant="primary" data-test="save-gateways-button">{{ __('Save') }}</x-ui.button>
                    </x-form.actions>
                </form>
            </flux:card>

            <flux:card>
                <form wire:submit="saveManualNumbers" class="space-y-5">
                    <div>
                        <flux:heading>{{ __('Manual Payment') }}</flux:heading>
                        <flux:text class="mt-1">{{ __('Optional numbers for staff to share when customers ask for payment details.') }}</flux:text>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <flux:input wire:model="bkashNumber" inputmode="numeric" :label="__('bKash Number')" placeholder="01712345678" />
                        <flux:input wire:model="nagadNumber" inputmode="numeric" :label="__('Nagad Number')" placeholder="01712345678" />
                    </div>

                    <x-form.actions>
                        <x-ui.button type="submit" variant="primary" data-test="save-manual-numbers-button">{{ __('Save') }}</x-ui.button>
                    </x-form.actions>
                </form>
            </flux:card>
        </div>
    </x-backoffice.layout.main-content-container>
</section>
