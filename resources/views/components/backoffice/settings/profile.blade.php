<section class="w-full">
    <flux:heading level="2" class="sr-only">{{ __('Profile settings') }}</flux:heading>

    <x-backoffice.settings.layout :heading="__('Profile')" :subheading="__('Update your name, email address, mobile number and addresses')">
        <form wire:submit="updateProfileInformation" class="w-full space-y-6">
            <x-media.avatar-upload :name="$name" :url="$this->avatarUrl" :accept="mediaAccept('user.media.avatar')" />
            <flux:input wire:model="name" :label="__('Name')" type="text" required autofocus autocomplete="name" />

            <div>
                <flux:input wire:model="email" :label="__('Email')" type="email" required autocomplete="email" />

                @if ($this->hasUnverifiedEmail)
                    <div>
                        <flux:text class="mt-4">
                            {{ __('Your email address is unverified.') }}

                            <flux:link class="text-sm cursor-pointer" wire:click.prevent="resendVerificationNotification">
                                {{ __('Click here to re-send the verification email.') }}
                            </flux:link>
                        </flux:text>

                        @if (session('status') === 'verification-link-sent')
                            <flux:text class="mt-2 font-medium !dark:text-green-400 !text-green-600">
                                {{ __('A new verification link has been sent to your email address.') }}
                            </flux:text>
                        @endif
                    </div>
                @endif
            </div>

            <flux:input wire:model="phone" :label="__('Mobile Number')" type="tel" inputmode="numeric" :maxlength="validPhoneLength()" placeholder="01XXXXXXXXX" autocomplete="tel" />

            <x-form.actions>
                <x-ui.button variant="primary" type="submit" data-test="update-profile-button">{{ __('Save') }}</x-ui.button>
            </x-form.actions>
        </form>

        <flux:separator class="my-8" />

        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Addresses') }}</flux:heading>
                <flux:subheading>{{ __('Your shipping and billing addresses.') }}</flux:subheading>
            </div>
            <x-form.address-form />
        </div>
    </x-backoffice.settings.layout>
</section>
