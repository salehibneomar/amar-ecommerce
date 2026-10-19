<div class="flex min-h-0 flex-1 flex-col" x-data="{ pending: '' }">
    <div class="border-b border-line bg-surface">
        <div class="mx-auto flex h-14 max-w-3xl items-center gap-3 px-4 sm:px-6">
            <span class="flex size-8 items-center justify-center rounded-lg bg-brand-100 text-brand-700 dark:bg-brand-900 dark:text-brand-200">
                <span class="material-symbols-outlined text-lg!">auto_awesome</span>
            </span>
            <h1 class="font-semibold text-zinc-900 dark:text-white">{{ __('Ask AI') }}</h1>
            <x-ui.badge color="amber">{{ __('Beta') }}</x-ui.badge>
            @if ($messages)
                <flux:button size="sm" variant="ghost" icon="trash" icon:variant="outline" class="ms-auto font-normal! text-zinc-500! hover:text-zinc-700! dark:text-zinc-400! dark:hover:text-zinc-200!" wire:click="clear">
                    {{ __('Clear') }}</flux:button>
            @endif
        </div>
    </div>

    <div class="scrollbar-hidden flex min-h-0 flex-1 flex-col-reverse overflow-y-auto">
        <div class="mx-auto flex w-full max-w-3xl flex-1 flex-col gap-6 px-4 py-8 sm:px-6">
            @if (!$messages)
                <div wire:loading.remove wire:target="send"
                    class="my-auto flex flex-col items-center gap-8 text-center">
                    <div class="flex flex-col items-center gap-3">
                        <span
                            class="flex size-14 items-center justify-center rounded-2xl bg-brand-50 text-brand-600 dark:bg-brand-950 dark:text-brand-300">
                            <span class="material-symbols-outlined text-3xl!">auto_awesome</span>
                        </span>
                        <h2 class="text-xl font-semibold tracking-tight text-zinc-900 sm:text-2xl dark:text-white">
                            {{ __('What are you looking for?') }}</h2>
                        <p class="max-w-md text-sm text-zinc-500 dark:text-zinc-400">
                            {{ __('Tell me your details, I will help you find the best one from our inventory.') }}</p>
                    </div>
                    @if ($examples)
                        <div class="grid w-full gap-3 sm:grid-cols-3">
                            @foreach ($examples as $example)
                                <button type="button"
                                    class="flex items-center gap-3 rounded-xl shadow-card border border-line bg-surface px-4 py-3 sm:flex-col sm:items-start sm:gap-2 sm:py-4 text-start text-sm text-zinc-700 transition-colors hover:border-brand-500 hover:text-brand-700 dark:text-zinc-200"
                                    x-on:click="$wire.message = @js($example); $nextTick(() => $refs.form.requestSubmit())">
                                    <span class="material-symbols-outlined text-brand-500">chat_bubble</span>
                                    <span>{{ $example }}</span>
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif

            @foreach ($messages as $index => $item)
                @if ($item['role'] === $userRole)
                    <div wire:key="assistant-message-{{ $index }}" class="flex items-start justify-end gap-3">
                        <div class="max-w-[85%] rounded-2xl rounded-tr-md bg-zinc-200/70 px-5 py-3 text-sm break-words text-zinc-900 dark:bg-zinc-800 dark:text-zinc-50">{!! nl2br(e($item['text'])) !!}</div>
                        <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-zinc-200/70 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                            <span class="material-symbols-outlined text-lg!">person</span>
                        </span>
                    </div>
                @else
                    <div wire:key="assistant-message-{{ $index }}" class="flex items-start gap-3">
                        <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-brand-100 text-brand-700 dark:bg-brand-900 dark:text-brand-200">
                            <span class="material-symbols-outlined text-lg!">auto_awesome</span>
                        </span>
                        <div class="flex max-w-[85%] min-w-0 flex-col gap-2">
                            <div class="w-fit rounded-2xl rounded-tl-md bg-brand-100 px-5 py-3 text-sm leading-relaxed break-words text-brand-950 dark:bg-brand-900 dark:text-brand-50">{!! nl2br(e($item['text'])) !!}</div>
                            @foreach ($item['suggestions'] as $suggestion)
                                @if ($product = $this->products->get($suggestion['id']))
                                    <x-frontend.cards.product-list-item wire:key="assistant-suggestion-{{ $index }}-{{ $product->id }}" :product="$product" :note="$suggestion['reason']" />
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif
            @endforeach

            <div wire:loading.flex wire:target="send" class="flex-col gap-6">
                <div class="flex items-start justify-end gap-3">
                    <div class="max-w-[85%] rounded-2xl rounded-tr-md bg-zinc-200/70 px-5 py-3 text-sm break-words text-zinc-900 dark:bg-zinc-800 dark:text-zinc-50" x-text="pending"></div>
                    <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-zinc-200/70 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                        <span class="material-symbols-outlined text-lg!">person</span>
                    </span>
                </div>
                <div class="flex items-center gap-3">
                    <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-brand-100 text-brand-700 dark:bg-brand-900 dark:text-brand-200">
                        <span class="material-symbols-outlined animate-pulse text-lg!">auto_awesome</span>
                    </span>
                    <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Thinking…') }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="shrink-0">
        <div class="mx-auto max-w-3xl px-4 pt-2 pb-4 sm:px-6">
            <form x-ref="form" wire:submit="send"
                x-on:submit="pending = $wire.message; $refs.input.style.height = 'auto'"
                class="flex items-end gap-2 rounded-2xl border border-line bg-surface p-2 shadow-sm transition-colors focus-within:border-brand-500">
                <textarea x-ref="input" wire:model="message" rows="1" maxlength="{{ aiAssistantMessageMaxLength() }}"
                    placeholder="{{ __('Ask about books…') }}" aria-label="{{ __('Message') }}"
                    x-on:input="$el.style.height = 'auto'; $el.style.height = Math.min($el.scrollHeight, 200) + 'px'"
                    x-on:keydown.enter="if (! $event.shiftKey) { $event.preventDefault(); $refs.form.requestSubmit() }"
                    wire:loading.attr="disabled" wire:target="send"
                    class="max-h-50 min-h-10 flex-1 resize-none bg-transparent px-2 py-2 text-sm text-zinc-900 placeholder:text-zinc-400 focus:outline-none dark:text-white"></textarea>
                <flux:button type="submit" variant="primary" square icon="arrow-up" :aria-label="__('Send')"
                    wire:loading.attr="disabled" wire:target="send" />
            </form>
            <flux:error name="message" class="mt-2" />
            <p class="mt-2 text-center text-xs text-zinc-400">
                {{ __('AI can make mistakes. Check prices and details on the book page.') }}</p>
        </div>
    </div>
</div>
