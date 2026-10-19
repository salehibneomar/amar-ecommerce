@props([
    'name',
    'src',
    'alt' => '',
    'title' => null,
])

<x-ui.modal :name="$name" class="w-full max-w-[calc(100vw-2rem)] sm:max-w-4xl">
    <div class="space-y-4">
        @if ($title)
            <flux:heading size="lg">{{ $title }}</flux:heading>
        @endif
        <img src="{{ $src }}" alt="{{ $alt }}" loading="lazy" class="mx-auto max-h-[75vh] w-auto rounded-lg object-contain" />
    </div>
</x-ui.modal>
