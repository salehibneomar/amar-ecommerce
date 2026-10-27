<section class="w-full">
    <x-backoffice.layout.main-content-container :title="__('SEO')" :subtitle="__('Search engine defaults, link previews and Google verification')" :breadcrumbs="[['label' => __('Settings'), 'href' => route('settings.index')], ['label' => __('SEO')]]">
        <flux:card>
            <form wire:submit="save" class="space-y-5">
                @if ($isIndexingLocked)
                    <x-ui.alert icon="exclamation-triangle" color="amber" :heading="__('Search engines are blocked on this site')" :text="__('SEO_INDEXING_ENABLED is false in .env, so every page stays hidden whatever is set here.')" />
                @endif

                <x-backoffice.form.toggle-field wire:model="indexingEnabled" :label="__('Allow Search Engines')" :disabled="$isIndexingLocked" />

                <x-backoffice.seo-meta.fields
                    :image-field="$this->seoMetaImageField"
                    :image="$seoMetaImage"
                    :remove-image="$removeSeoMetaImage"
                    :title-label="__('Default Title')"
                    :description-label="__('Default Description')"
                    :title-placeholder="config('app.name')"
                    :description-placeholder="seoDescription()"
                />

                <flux:input wire:model="googleSiteVerification" :label="__('Google Verification Code')" :placeholder="__('Content value of the Google Search Console HTML tag')" />

                <x-ui.copy-text :value="$sitemapUrl" :label="__('Sitemap URL')" :description="__('Submit this link in Google Search Console.')" />

                <x-form.actions>
                    <x-ui.button type="submit" variant="primary" data-test="save-seo-button">{{ __('Save') }}</x-ui.button>
                </x-form.actions>
            </form>
        </flux:card>
    </x-backoffice.layout.main-content-container>
</section>
