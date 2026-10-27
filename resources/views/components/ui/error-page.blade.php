@props([
    'code',
    'title',
    'message',
    'note' => null,
    'actions' => true,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <x-layouts.head :title="$title" />
    </head>
    <body class="flex min-h-svh items-center justify-center bg-canvas px-4 text-zinc-800 antialiased dark:text-zinc-100">
        <main class="w-full max-w-xl text-center">
            @if ($actions)
                <a href="{{ route('home') }}" class="mx-auto mb-10 flex w-fit items-center">
                    <x-brand.app-logo-icon class="h-14 w-auto" />
                </a>
            @else
                <x-brand.app-logo-icon class="mx-auto mb-10 h-14 w-auto" />
            @endif

            <p class="text-8xl font-bold tracking-tight text-brand-text sm:text-9xl">{{ $code }}</p>
            <h1 class="mt-4 text-2xl font-semibold tracking-tight text-zinc-900 dark:text-white">{{ $title }}</h1>
            <p class="mt-4 text-lg text-zinc-600 dark:text-zinc-400">{{ $message }}</p>

            @if ($note)
                <p class="mt-3 text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ $note }}</p>
            @endif

            @if ($actions)
                <div class="mt-8 flex flex-wrap justify-center gap-3">
                    <x-ui.button variant="primary" :href="route('home')">{{ __('Go to Home Page') }}</x-ui.button>
                    <x-ui.button :href="route('contact')">{{ __('Contact Us') }}</x-ui.button>
                </div>
            @endif
        </main>
    </body>
</html>
