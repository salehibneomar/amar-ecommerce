<div class="flex min-h-0 flex-1 flex-col" x-data="{ pending: '' }">
    <div class="border-b border-line bg-subtle">
        <div class="mx-auto flex h-14 max-w-3xl items-center gap-3 px-4 sm:px-6">
            <x-ui.icon-badge size="xs">
                <span class="material-symbols-outlined text-lg!">auto_awesome</span>
            </x-ui.icon-badge>
            <h1 class="font-semibold text-zinc-900 dark:text-white">{{ __('Ask AI') }}</h1>
            <x-ui.badge color="amber">{{ __('Beta') }}</x-ui.badge>
            @if ($messages)
                <x-ui.button size="sm" variant="ghost" icon="trash" icon:variant="outline" class="ms-auto" wire:click="clear">
                    {{ __('Clear') }}</x-ui.button>
            @endif
        </div>
    </div>

    <div class="scrollbar-hidden flex min-h-0 flex-1 flex-col-reverse overflow-y-auto">
        <div class="mx-auto flex w-full max-w-3xl flex-1 flex-col gap-6 px-4 py-8 sm:px-6">
            @if (!$messages)
                <div wire:loading.remove wire:target="send"
                    class="my-auto flex flex-col items-center gap-8 text-center">
                    <div class="flex flex-col items-center gap-3">
                        <x-ui.icon-badge size="xl">
                            <span class="material-symbols-outlined text-3xl!">auto_awesome</span>
                        </x-ui.icon-badge>
                        <h2 class="text-xl font-semibold tracking-tight text-zinc-900 sm:text-2xl dark:text-white">
                            {{ __('What are you looking for?') }}</h2>
                        <p class="max-w-md text-sm text-zinc-500 dark:text-zinc-400">
                            {{ __('Tell me your details, I will help you find the best one from our inventory.') }}</p>
                    </div>
                    @if ($examples)
                        <div class="grid w-full gap-3 sm:grid-cols-3">
                            @foreach ($examples as $example)
                                <x-frontend.cards.card as="button" type="button" interactive :padded="false"
                                    class="flex cursor-pointer items-center gap-3 px-4 py-3 text-start text-sm text-zinc-700 sm:flex-col sm:items-start sm:gap-2.5 sm:py-4 dark:text-zinc-200"
                                    x-on:click="$wire.message = {{ Js::from($example) }}; $nextTick(() => $refs.form.requestSubmit())">
                                    <x-ui.icon-badge size="xs">
                                        <span class="material-symbols-outlined text-lg!">chat_bubble</span>
                                    </x-ui.icon-badge>
                                    <span>{{ $example }}</span>
                                </x-frontend.cards.card>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif

            @foreach ($messages as $index => $item)
                @if ($item['role'] === $userRole)
                    <div wire:key="assistant-message-{{ $index }}" class="flex items-start justify-end gap-3">
                        <div class="max-w-[85%] rounded-xl rounded-tr-sm border border-accent/22 bg-accent/14 px-5 py-3 text-sm break-words text-zinc-900 dark:border-accent/35 dark:bg-accent/26 dark:text-zinc-50">{!! nl2br(e($item['text'])) !!}</div>
                        @auth
                            <x-ui.avatar :src="auth()->user()->avatar_url" :initials="auth()->user()->initials()" size="sm" />
                        @else
                            <x-ui.icon-badge size="sm">
                                <span class="material-symbols-outlined text-lg!">person</span>
                            </x-ui.icon-badge>
                        @endauth
                    </div>
                @else
                    <div wire:key="assistant-message-{{ $index }}" class="flex items-start gap-3">
                        <x-ui.icon-badge size="sm">
                            <span class="material-symbols-outlined text-lg!">auto_awesome</span>
                        </x-ui.icon-badge>
                        <div class="flex max-w-[85%] min-w-0 flex-col gap-2">
                            <x-ui.card class="w-fit rounded-tl-sm px-5 py-3 text-sm leading-relaxed break-words text-zinc-800 dark:text-zinc-100">{!! nl2br(e($item['text'])) !!}</x-ui.card>
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
                    <div class="max-w-[85%] rounded-xl rounded-tr-sm border border-accent/22 bg-accent/14 px-5 py-3 text-sm break-words text-zinc-900 dark:border-accent/35 dark:bg-accent/26 dark:text-zinc-50" x-text="pending"></div>
                    @auth
                        <x-ui.avatar :src="auth()->user()->avatar_url" :initials="auth()->user()->initials()" size="sm" />
                    @else
                        <x-ui.icon-badge size="sm">
                            <span class="material-symbols-outlined text-lg!">person</span>
                        </x-ui.icon-badge>
                    @endauth
                </div>
                <div class="flex items-center gap-3">
                    <x-ui.icon-badge size="sm">
                        <span class="material-symbols-outlined animate-pulse text-lg!">auto_awesome</span>
                    </x-ui.icon-badge>
                    <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Thinking…') }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="shrink-0">
        <div class="mx-auto max-w-3xl px-4 pt-2 pb-4 sm:px-6">
            <form x-ref="form" wire:submit="send"
                x-on:submit="pending = $wire.message; $refs.input.style.height = 'auto'"
                class="flex items-end gap-2 rounded-xl border border-line bg-surface p-2 shadow-card transition-colors focus-within:border-brand-400 focus-within:outline-hidden focus-within:ring-2 focus-within:ring-accent focus-within:ring-offset-2 focus-within:ring-offset-accent-foreground dark:focus-within:border-brand-600">
                <textarea x-ref="input" wire:model="message" rows="1" maxlength="{{ aiAssistantMessageMaxLength() }}"
                    placeholder="{{ __('Ask about products…') }}" aria-label="{{ __('Message') }}"
                    x-on:input="$el.style.height = 'auto'; $el.style.height = Math.min($el.scrollHeight, 200) + 'px'"
                    x-on:keydown.enter="if (! $event.shiftKey) { $event.preventDefault(); $refs.form.requestSubmit() }"
                    wire:loading.attr="disabled" wire:target="send"
                    class="max-h-50 min-h-10 flex-1 resize-none bg-transparent px-2 py-2 text-sm text-zinc-900 placeholder:text-zinc-400 focus:outline-none dark:text-white"></textarea>
                <x-ui.button type="submit" variant="primary" square icon="arrow-up" :aria-label="__('Send')"
                    wire:loading.attr="disabled" wire:target="send" />
            </form>
            <flux:error name="message" class="mt-2" />
            <p class="mt-2 text-center text-xs text-zinc-500 dark:text-zinc-400">
                {{ __('AI can make mistakes. Check prices and details on the product page.') }}</p>
        </div>
    </div>
</div>
