@props([
    'title',
])

<section {{ $attributes->class(['space-y-3']) }}>
    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ $title }}</h2>
    <div class="space-y-3 text-sm leading-relaxed text-zinc-600 [&_li]:ms-5 [&_ol]:list-decimal [&_ol]:space-y-2 [&_ul]:list-disc [&_ul]:space-y-2 dark:text-zinc-300">
        {{ $slot }}
    </div>
</section>
