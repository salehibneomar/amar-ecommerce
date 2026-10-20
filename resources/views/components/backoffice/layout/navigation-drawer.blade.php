<x-ui.drawer name="backoffice-menu" side="start" mobile :label="__('Menu')" x-on:click="$event.target.closest('a') && (open = false)">
    <x-slot:header>
        <a href="{{ route('dashboard') }}" class="flex items-center gap-1.5" wire:navigate>
            <x-brand.app-logo-icon class="h-9 w-auto" />
            <x-brand.edition />
        </a>
        <div class="flex items-center gap-1">
            <x-ui.theme-toggle size="sm" />
            <x-ui.button variant="ghost" size="sm" bar icon="x-mark" x-on:click="open = false" :aria-label="__('Close menu')" />
        </div>
    </x-slot:header>

    <nav class="flex flex-col">
        <x-backoffice.layout.navigation />
    </nav>
</x-ui.drawer>
