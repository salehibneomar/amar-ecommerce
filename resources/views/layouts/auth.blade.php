<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="flex min-h-svh flex-col bg-zinc-100 antialiased dark:bg-canvas">
        <div class="fixed start-4 top-4 z-50">
            <flux:button
                href="{{ route('home') }}"
                variant="subtle"
                square
                class="cursor-pointer rounded-full border border-zinc-300 bg-white/80 text-zinc-600 backdrop-blur hover:bg-brand-50 hover:text-brand-700 dark:border-zinc-600 dark:bg-zinc-900/80 dark:text-zinc-300 dark:hover:bg-zinc-800 dark:hover:text-brand-300"
                aria-label="{{ __('Back to home') }}"
                wire:navigate
            >
                <span class="material-symbols-outlined text-lg">arrow_back</span>
            </flux:button>
        </div>

        <div class="fixed end-4 top-4 z-50">
            <x-ui.theme-toggle variant="auth" />
        </div>

        <div class="bg-background flex min-h-0 flex-1 flex-col items-center justify-center gap-6 p-6 md:p-10">
            <div class="flex w-full max-w-md flex-col gap-2">
                <div class="overflow-hidden rounded-2xl border border-line bg-surface shadow-card text-zinc-800 dark:text-zinc-100">
                    <div class="p-8 sm:p-10">
                        <a href="{{ route('home') }}" class="mb-3 flex flex-col items-center gap-2 font-medium" wire:navigate>
                            <span class="flex h-20 w-40 items-center justify-center">
                                <x-brand.app-logo-icon class="size-full" />
                            </span>
                            <span class="sr-only">{{ config('app.name', 'Laravel') }}</span>
                        </a>

                        {{ $slot }}
                    </div>
                </div>
            </div>
        </div>

        <x-brand.copyright />
        <x-ui.flash-toast />
        @fluxScripts
    </body>
</html>
