@props([
    'heading' => '',
    'subheading' => '',
])

<x-backoffice.layout.main-content-container :title="$heading" :subtitle="$subheading" :breadcrumbs="[['label' => __('Settings'), 'href' => route('settings.index')], ['label' => $heading]]">
    <flux:card>
        <div class="w-full">
            {{ $slot }}
        </div>
    </flux:card>
</x-backoffice.layout.main-content-container>
