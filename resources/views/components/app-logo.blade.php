@props([
    'sidebar' => false,
])

@if($sidebar)
    <flux:sidebar.brand {{ $attributes }}>
        <x-slot name="logo" class="flex h-9 w-full min-w-0 items-center justify-center rounded-md">
            <img
                src="{{ asset('images/logo-sanmal.png') }}"
                alt="SanMal"
                class="block h-8 max-w-full object-contain"
            />
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand {{ $attributes }}>
        <x-slot name="logo" class="flex h-9 w-full min-w-0 items-center justify-center rounded-md">
            <img
                src="{{ asset('images/logo-sanmal.png') }}"
                alt="SanMal"
                class="h-8 w-auto object-contain"
            />
        </x-slot>
    </flux:brand>
@endif
