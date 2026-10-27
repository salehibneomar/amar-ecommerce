@props([
    'title' => null,
])

<x-layouts.frontend :title="$title">
    <div class="flex justify-center px-4 py-12 sm:py-20">
        <x-ui.card class="w-full max-w-md p-6 sm:p-8">
            {{ $slot }}
        </x-ui.card>
    </div>
</x-layouts.frontend>
