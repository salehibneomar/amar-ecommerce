<x-frontend.layout.page :title="__('Profile')" :subtitle="__('Update your photo, name, email address and mobile number.')">
    <x-frontend.cards.card>
        <form wire:submit="updateProfile" class="space-y-6">
            <x-media.avatar-upload :name="$name" :url="$this->avatarUrl" :accept="mediaAccept('user.media.avatar')" />
            <flux:input wire:model="name" :label="__('Name')" type="text" required autocomplete="name" />
            <flux:input wire:model="email" :label="__('Email Address')" type="email" required autocomplete="email" />
            <flux:input wire:model="phone" :label="__('Mobile Number')" type="tel" inputmode="numeric" :maxlength="validPhoneLength()" placeholder="01XXXXXXXXX" autocomplete="tel" />

            <div class="flex justify-end">
                <x-ui.button variant="primary" type="submit">{{ __('Save') }}</x-ui.button>
            </div>
        </form>
    </x-frontend.cards.card>

    <x-frontend.cards.card :title="__('Connected accounts')" :description="__('Social accounts you can use to log in.')">
        <div class="divide-y divide-line">
            @foreach ($this->socialAccounts as $account)
                <div wire:key="social-{{ $account['label'] }}" class="flex items-center justify-between py-3">
                    <span class="flex items-center gap-3 text-sm font-medium">
                        <x-auth.provider-logo :provider="$account['provider']" />
                        {{ $account['label'] }}
                    </span>
                    <x-ui.badge :color="$account['connected'] ? 'green' : 'zinc'">
                        {{ $account['connected'] ? __('Connected') : __('Not connected') }}
                    </x-ui.badge>
                </div>
            @endforeach
        </div>
    </x-frontend.cards.card>
</x-frontend.layout.page>
