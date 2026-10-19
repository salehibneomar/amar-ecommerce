@props([
    'seconds' => 0,
    'message' => __('You can request a new link in'),
])

<div
    x-data="{
        remaining: {{ max(0, (int) $seconds) }},
        init() {
            const timer = setInterval(() => this.remaining > 0 ? this.remaining-- : clearInterval(timer), 1000);
        },
        get clock() {
            return String(Math.floor(this.remaining / 60)).padStart(2, '0') + ':' + String(this.remaining % 60).padStart(2, '0');
        },
    }"
    {{ $attributes->class(['flex flex-col gap-6']) }}
>
    <fieldset class="contents" x-bind:disabled="remaining > 0" @disabled($seconds > 0)>
        {{ $slot }}
    </fieldset>

    <p x-show="remaining > 0" x-cloak class="flex items-center justify-center gap-1.5 text-sm text-zinc-500 dark:text-zinc-400" data-test="resend-countdown">
        <span class="material-symbols-outlined text-base">schedule</span>
        <span>{{ $message }} <span class="font-mono font-medium text-zinc-800 tabular-nums dark:text-white" x-text="clock"></span></span>
    </p>
</div>
