@props([
    'question',
    'answer',
])

<details {{ $attributes->class(['group rounded-xl shadow-card border border-zinc-200/80 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-950']) }}>
    <summary class="flex cursor-pointer list-none items-center justify-between font-medium text-zinc-900 dark:text-white">
        {{ $question }}
        <span class="material-symbols-outlined text-zinc-400 transition-transform group-open:rotate-180">expand_more</span>
    </summary>
    <p class="mt-3 text-sm whitespace-pre-line text-zinc-600 dark:text-zinc-400">{{ $answer }}</p>
</details>
