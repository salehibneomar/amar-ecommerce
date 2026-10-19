<footer class="border-t glass-bar">
    <div class="mx-auto flex max-w-shell flex-col items-center justify-between gap-4 px-4 py-8 sm:flex-row sm:px-6">
        <x-brand.app-logo-icon class="h-10 w-auto sm:h-11" />
        <nav class="flex flex-wrap justify-center gap-y-2 divide-x divide-bar-line text-sm" aria-label="{{ __('Legal') }}">
            <a href="{{ route('terms') }}" class="px-3 leading-none text-bar-foreground transition-colors hover:text-bar-active-foreground" wire:navigate>{{ __('Terms of use') }}</a>
            <a href="{{ route('privacy') }}" class="px-3 leading-none text-bar-foreground transition-colors hover:text-bar-active-foreground" wire:navigate>{{ __('Privacy policy') }}</a>
            <a href="{{ route('refund-policy') }}" class="px-3 leading-none text-bar-foreground transition-colors hover:text-bar-active-foreground" wire:navigate>{{ __('Refund and order policy') }}</a>
        </nav>
        <x-brand.copyright class="p-0 font-normal! text-bar-foreground!" />
    </div>
</footer>
