@props([
    'user' => null,
])

@php
    $user = $user ?? auth()->user();
@endphp

@if ($user)
    <flux:dropdown position="bottom" align="start">
        <flux:profile
            :name="$user->name"
            :initials="$user->initials()"
            :avatar="$user->profilePhotoUrl()"
            data-test="sidebar-menu-button"
        />

        <flux:menu>
            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                <x-ui.avatar
                    :name="$user->name"
                    :initials="$user->initials()"
                    :src="$user->profilePhotoUrl()"
                    size="sm"
                />
                <div class="grid flex-1 text-start text-sm leading-tight">
                    <flux:heading class="truncate">{{ $user->name }}</flux:heading>
                    <flux:text class="truncate">{{ $user->email }}</flux:text>
                </div>
            </div>
            <flux:menu.separator />
            <flux:menu.item :href="route('profile.edit')" icon="user-circle" wire:navigate>
                {{ __('Profil') }}
            </flux:menu.item>
            <flux:menu.item :href="route('security.edit')" icon="shield-check" wire:navigate>
                {{ __('Keamanan Akun') }}
            </flux:menu.item>
            <form method="POST" action="{{ route('logout') }}" class="w-full">
                @csrf
                <flux:menu.item
                    as="button"
                    type="submit"
                    icon="arrow-right-start-on-rectangle"
                    class="w-full cursor-pointer"
                    data-test="logout-button"
                >
                    {{ __('Log out') }}
                </flux:menu.item>
            </form>
        </flux:menu>
    </flux:dropdown>
@endif
