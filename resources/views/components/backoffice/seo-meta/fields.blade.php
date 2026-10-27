@props([
    'imageField' => null,
    'image' => null,
    'removeImage' => false,
    'noindex' => false,
    'titleLabel' => __('SEO Title'),
    'descriptionLabel' => __('SEO Description'),
    'titlePlaceholder' => __('Empty uses the default title'),
    'descriptionPlaceholder' => __('Empty uses the default description'),
])

<div x-data {{ $attributes->class('space-y-5') }}>
    <flux:field>
        <flux:label class="w-full gap-2">
            {{ $titleLabel }}
            <span class="ms-auto text-xs text-zinc-400 tabular-nums dark:text-zinc-500" x-text="($wire.seoMetaTitle?.length ?? 0) + ' / 70'"></span>
        </flux:label>
        <flux:input wire:model="seoMetaTitle" maxlength="70" :placeholder="$titlePlaceholder" />
        <flux:error name="seoMetaTitle" />
    </flux:field>

    <flux:field>
        <flux:label class="w-full gap-2">
            {{ $descriptionLabel }}
            <span class="ms-auto text-xs text-zinc-400 tabular-nums dark:text-zinc-500" x-text="($wire.seoMetaDescription?.length ?? 0) + ' / 160'"></span>
        </flux:label>
        <flux:textarea wire:model="seoMetaDescription" maxlength="160" rows="3" :placeholder="$descriptionPlaceholder" />
        <flux:error name="seoMetaDescription" />
    </flux:field>

    @if ($imageField)
        <x-media.upload :label="__('Share Image')" :field="$imageField" upload="seoMetaImage" remove="removeSeoMetaImage" :selected="$image" :marked-for-removal="$removeImage" />
    @endif

    @if ($noindex)
        <x-backoffice.form.toggle-field wire:model="seoMetaNoindex" :label="__('Hide From Search')" />
    @endif
</div>
