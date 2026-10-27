@props([
    'imageField' => null,
    'image' => null,
    'removeImage' => false,
])

<x-backoffice.management.form-modal wire:model="showSeoMetaForm" :title="__('SEO')" :subtitle="__('Empty fields use the name, description and image of this item.')" submit="saveSeoMeta" size="lg">
    <x-backoffice.seo-meta.fields
        :image-field="$imageField"
        :image="$image"
        :remove-image="$removeImage"
        :noindex="isSeoPageNoindexEnabled()"
        :title-placeholder="__('Empty uses the name')"
        :description-placeholder="__('Empty uses the description')"
    />
</x-backoffice.management.form-modal>
