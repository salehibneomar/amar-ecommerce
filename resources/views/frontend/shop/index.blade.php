<x-layouts::frontend :title="__('Shop')">
    <x-frontend.layout.page :title="__('Shop')" :subtitle="$isPhysicalOnly ? __('Read a few sample pages before you order your copy.') : __('Every paid book comes with a free preview of the first :pages pages.', ['pages' => bookPreviewPages()])" width="max-w-main">
        <livewire:frontend.shop.index />
    </x-frontend.layout.page>

    <livewire:frontend.reader.index />
</x-layouts::frontend>
