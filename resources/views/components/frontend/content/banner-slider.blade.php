@props([
    'slides' => [],
    'interval' => 6000,
])

<div
    x-data="{
        active: 0,
        total: {{ count($slides) }},
        timer: null,
        go(index) { this.active = (index + this.total) % this.total },
        start() { if (this.total > 1) { this.timer = setInterval(() => this.go(this.active + 1), {{ (int) $interval }}) } },
        stop() { clearInterval(this.timer) },
        startX: null,
        swipeStart(event) { this.startX = event.clientX },
        swipeEnd(event) {
            if (this.startX === null) { return }
            const distance = event.clientX - this.startX
            this.startX = null
            if (Math.abs(distance) > 40) { this.go(this.active + (distance < 0 ? 1 : -1)) }
        },
    }"
    x-init="start()"
    x-on:mouseenter="stop()"
    x-on:mouseleave="start()"
    x-on:pointerdown="swipeStart($event)"
    x-on:pointerup="swipeEnd($event)"
    x-on:pointercancel="startX = null"
    {{ $attributes->class(['relative touch-pan-y select-none overflow-hidden rounded-2xl bg-brand-100 dark:bg-brand-950']) }}
    role="region"
    aria-roledescription="carousel"
    aria-label="{{ __('Featured') }}"
>
    <div class="relative aspect-[16/10] sm:aspect-[16/7] lg:aspect-[16/6]">
        @foreach ($slides as $index => $slide)
            <div
                x-show="active === {{ $index }}"
                x-transition.opacity.duration.400ms
                @if ($index > 0) style="display: none" @endif
                class="absolute inset-0"
                role="group"
                aria-roledescription="slide"
                aria-label="{{ $index + 1 }} / {{ count($slides) }}"
            >
                @if ($slide['image'])
                    <img src="{{ $slide['image'] }}" alt="{{ $slide['title'] }}" class="size-full object-cover" draggable="false" @if ($index > 0) loading="lazy" @endif />
                @else
                    <div class="size-full bg-brand-100 dark:bg-brand-900"></div>
                @endif

                @if ($slide['show_text'])
                    <div class="absolute inset-0 bg-zinc-950/35"></div>
                    <div class="absolute inset-0 flex items-center">
                        <div class="max-w-xl space-y-3 px-6 sm:space-y-4 sm:px-12">
                            <h2 class="text-2xl font-bold leading-tight text-white sm:text-4xl lg:text-5xl">{{ $slide['title'] }}</h2>
                            @if ($slide['subtitle'])
                                <p class="text-sm text-white/90 sm:text-lg">{{ $slide['subtitle'] }}</p>
                            @endif
                            @if ($slide['link'])
                                <a href="{{ $slide['link'] }}" class="inline-flex items-center gap-2 rounded-lg bg-white/90 px-5 py-2.5 text-sm font-semibold text-brand-800 transition-colors hover:bg-white" @if (str_starts_with($slide['link'], '/')) wire:navigate @endif>
                                    {{ __('Shop now') }}
                                    <flux:icon.arrow-right variant="micro" />
                                </a>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    @if (count($slides) > 1)
        <div class="absolute inset-x-0 bottom-2 z-10 flex justify-center">
            @foreach ($slides as $index => $slide)
                <button type="button" x-on:pointerdown.stop x-on:click="stop(); go({{ $index }})" class="group p-2" aria-label="{{ __('Go to slide :number', ['number' => $index + 1]) }}">
                    <span class="block h-2 rounded-full transition-all" x-bind:class="active === {{ $index }} ? 'w-7 bg-white' : 'w-2 bg-white/50 group-hover:bg-white/80'"></span>
                </button>
            @endforeach
        </div>
    @endif
</div>
