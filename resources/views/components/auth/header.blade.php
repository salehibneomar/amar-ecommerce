@props([
    'title',
    'description',
])

<div class="flex w-full flex-col gap-1.5 text-center">
    <flux:heading size="xl" level="1" class="text-2xl! font-semibold tracking-tight">{{ $title }}</flux:heading>
    <flux:subheading>{{ $description }}</flux:subheading>
</div>
