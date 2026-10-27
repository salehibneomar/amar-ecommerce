<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <x-layouts.head :title="$title ?? null" />
    </head>
    <body class="flex min-h-svh flex-col bg-canvas antialiased">
        <div class="fixed start-4 top-4 z-50">
            <x-ui.button
                href="{{ route('home') }}"
                color="overlay"
                square
                class="cursor-pointer"
                aria-label="{{ __('Back to home') }}"
                wire:navigate
            >
                <span class="material-symbols-outlined text-lg">arrow_back</span>
            </x-ui.button>
        </div>

        <div class="fixed end-4 top-4 z-50">
            <x-ui.theme-toggle variant="auth" />
        </div>

        <div class="flex min-h-0 flex-1 flex-col items-center justify-center gap-6 p-6 md:p-10">
            <div class="flex w-full max-w-md flex-col gap-2">
                <x-ui.card class="overflow-hidden text-zinc-800 dark:text-zinc-100">
                    <div class="p-8 sm:p-10">
                        <a href="{{ route('home') }}" class="mb-3 flex flex-col items-center gap-2 font-medium" wire:navigate>
                            <span class="flex h-20 w-40 items-center justify-center">
                                <x-brand.app-logo-icon class="size-full" />
                            </span>
                            <span class="sr-only">{{ config('app.name', 'Laravel') }}</span>
                        </a>

                        {{ $slot }}
                    </div>
                </x-ui.card>
            </div>
        </div>

        <x-brand.copyright />
        <x-ui.flash-toast />
        @fluxScripts
    </body>
</html>
