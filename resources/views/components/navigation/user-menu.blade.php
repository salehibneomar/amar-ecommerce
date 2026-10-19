@props([
    'settingsUrl' => route('settings.index'),
    'bar' => false,
])

<x-ui.dropdown>
    <x-slot:trigger>
        <x-ui.button
            variant="subtle"
            :bar="$bar"
            class="cursor-pointer gap-2 max-sm:h-8! max-sm:px-1!"
            data-test="topbar-menu-button"
        >
            <x-ui.avatar
                :src="auth()->user()->avatar_url"
                :initials="auth()->user()->initials()"
                size="sm"
                :bar="$bar"
            />
            <span class="material-symbols-outlined max-sm:hidden!">expand_more</span>
        </x-ui.button>
    </x-slot:trigger>

    <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
        <x-ui.avatar
            :src="auth()->user()->avatar_url"
            :name="auth()->user()->name"
            :initials="auth()->user()->initials()"
        />
        <div class="grid flex-1 text-start text-sm leading-tight">
            <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
            <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
        </div>
    </div>
    <flux:menu.separator />
    <flux:menu.radio.group>
        @if ($slot->isEmpty())
            <x-ui.menu-item :href="$settingsUrl" icon="cog" wire:navigate>
                {{ __('Settings') }}
            </x-ui.menu-item>
        @else
            {{ $slot }}
        @endif
        <form method="POST" action="{{ route('logout') }}" class="w-full">
            @csrf
            <x-ui.menu-item
                as="button"
                type="submit"
                icon="arrow-right-start-on-rectangle"
                class="w-full cursor-pointer"
                data-test="logout-button"
            >
                {{ __('Log Out') }}
            </x-ui.menu-item>
        </form>
    </flux:menu.radio.group>
</x-ui.dropdown>
