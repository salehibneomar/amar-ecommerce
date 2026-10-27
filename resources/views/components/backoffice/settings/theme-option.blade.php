@props([
    'value',
    'label',
    'theme',
    'model',
    'shades' => [100, 300, 500, 700, 900],
])

<label {{ $attributes->class('group relative flex cursor-pointer flex-col gap-3 rounded-xl border border-line bg-surface p-3 shadow-card transition hover:border-brand-300 has-[:checked]:border-brand has-[:checked]:ring-2 has-[:checked]:ring-brand/30 has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-brand/50') }}>
    <input type="radio" value="{{ $value }}" wire:model="{{ $model }}" class="sr-only" />

    <div class="flex h-10 overflow-hidden rounded-lg">
        @foreach ($shades as $shade)
            <span class="flex-1" style="background-color: {{ $theme['palette'][$shade] }}"></span>
        @endforeach
    </div>

    <span class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $label }}</span>

    <flux:icon.check-circle class="absolute end-2 top-2 hidden size-5 rounded-full bg-surface text-brand group-has-[:checked]:block" />
</label>
