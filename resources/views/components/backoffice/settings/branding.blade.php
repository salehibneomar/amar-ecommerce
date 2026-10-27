<section class="w-full">
    <x-backoffice.layout.main-content-container :title="__('Branding')" :subtitle="__('Replace the logo, icon and favicon. A removed image falls back to the default.')" :breadcrumbs="[['label' => __('Settings'), 'href' => route('settings.index')], ['label' => __('Branding')]]">
        <flux:card>
            <form wire:submit="saveBrand" class="space-y-5">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <x-media.upload :label="__('Logo')" :field="$this->brandFields['logo']" upload="logoUpload" remove="removeLogo" :selected="$logoUpload" :marked-for-removal="$removeLogo" />
                    <x-media.upload :label="__('Icon (Small Square)')" :field="$this->brandFields['icon']" upload="iconUpload" remove="removeIcon" :selected="$iconUpload" :marked-for-removal="$removeIcon" />
                    <x-media.upload :label="__('Favicon')" :field="$this->brandFields['favicon']" upload="faviconUpload" remove="removeFavicon" :selected="$faviconUpload" :marked-for-removal="$removeFavicon" />
                </div>

                <x-form.actions>
                    <x-ui.button type="submit" variant="primary" data-test="save-brand-button">{{ __('Save') }}</x-ui.button>
                </x-form.actions>
            </form>
        </flux:card>
    </x-backoffice.layout.main-content-container>
</section>
