@props([
    'title' => null,
])

<x-layouts::frontend :title="$title">
    <div class="flex justify-center px-4 py-12 sm:py-16">
        <div class="w-full max-w-md rounded-2xl border border-zinc-200/80 bg-white shadow-card p-6 sm:p-8 dark:border-zinc-800 dark:bg-zinc-900">
            {{ $slot }}
        </div>
    </div>
</x-layouts::frontend>
