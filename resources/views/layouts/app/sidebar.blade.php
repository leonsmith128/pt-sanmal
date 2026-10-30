<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:sidebar sticky collapsible="mobile" class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.header>
                <div class="flex w-full items-center justify-between gap-2">
                    <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                    <flux:dropdown position="bottom" align="end">
                        <flux:button variant="ghost" size="sm" icon="sun" aria-label="{{ __('Change appearance') }}" />
                        <flux:menu>
                            <flux:menu.radio.group x-data x-model="$flux.appearance">
                                <flux:menu.radio value="light" icon="sun">{{ __('Light') }}</flux:menu.radio>
                                <flux:menu.radio value="dark" icon="moon">{{ __('Dark') }}</flux:menu.radio>
                                <flux:menu.radio value="system" icon="computer-desktop">{{ __('System') }}</flux:menu.radio>
                            </flux:menu.radio.group>
                        </flux:menu>
                    </flux:dropdown>
                </div>
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            @php
                $sidebarEmployee = auth()->user()->employee;
                $unseenRequestDecisions = $sidebarEmployee
                    ? $sidebarEmployee->leaveRequests()->whereIn('status', ['approved', 'rejected'])->whereNull('decision_seen_at')->count()
                        + $sidebarEmployee->overtimeRequests()->whereIn('approval_status', ['approved', 'rejected'])->whereNull('decision_seen_at')->count()
                        + $sidebarEmployee->attendanceCorrections()->whereIn('status', ['approved', 'rejected'])->whereNull('decision_seen_at')->count()
                    : 0;
            @endphp

            <flux:sidebar.nav>
                <flux:sidebar.group :heading="__('Platform')" class="grid">
                    <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                        {{ __('Dashboard') }}
                    </flux:sidebar.item>
                    
                @if (auth()->user()->employee)
                    <flux:sidebar.item icon="clock" :href="route('attendance')" :current="request()->routeIs('attendance')" wire:navigate>
                        {{ __('Absensi') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="calendar" :href="route('attendance-history.index')" :current="request()->routeIs('attendance-history.*')" wire:navigate>
                        {{ __('Riwayat Absensi') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="clipboard-document-list" :href="route('leave-requests.index')" :current="request()->routeIs('leave-requests.*')" :badge="$unseenRequestDecisions > 0 ? $unseenRequestDecisions : null" wire:navigate>
                        {{ __('Pusat Permohonan') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="check" :href="route('overtime-requests.index') . '#riwayat-lembur'" :current="request()->routeIs('overtime-requests.*')" wire:navigate>
                        {{ __('Riwayat Lembur') }}
                    </flux:sidebar.item>
                @endif
                </flux:sidebar.group>

                @if (auth()->user()->isManager())
                    <flux:sidebar.group :heading="__('Administrasi')" class="grid">
                        <flux:sidebar.item icon="clipboard-document-list" :href="route('admin.kehadiran')" :current="request()->routeIs('admin.kehadiran')" wire:navigate>
                            {{ __('Kehadiran') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="users" :href="route('admin.employees.index')" :current="request()->routeIs('admin.employees.*')" wire:navigate>
                            {{ __('Manajemen Pegawai') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="calendar" :href="route('admin.leave-requests.index')" :current="request()->routeIs('admin.leave-requests.*')" wire:navigate>
                            {{ __('Persetujuan Permohonan') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="clock" :href="route('admin.overtime-requests.index')" :current="request()->routeIs('admin.overtime-requests.*')" wire:navigate>
                            {{ __('Persetujuan Lembur') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="document-check" :href="route('admin.attendance-corrections.index')" :current="request()->routeIs('admin.attendance-corrections.*')" wire:navigate>
                            {{ __('Koreksi Absensi') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="calendar-days" :href="route('admin.daily-attendance')" :current="request()->routeIs('admin.daily-attendance*')" wire:navigate>
                            {{ __('Rekap Absensi Harian') }}
                        </flux:sidebar.item>
                    </flux:sidebar.group>
                @endif                
            </flux:sidebar.nav>

            <flux:spacer />

            <flux:sidebar.nav>
                <flux:sidebar.item icon="folder-git-2" href="https://github.com/laravel/livewire-starter-kit" target="_blank">
                    {{ __('Repository') }}
                </flux:sidebar.item>

                <flux:sidebar.item icon="book-open-text" href="https://laravel.com/docs/starter-kits#livewire" target="_blank">
                    {{ __('Documentation') }}
                </flux:sidebar.item>
            </flux:sidebar.nav>

            <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    :avatar="auth()->user()->profilePhotoUrl()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <flux:avatar
                                    :name="auth()->user()->name"
                                    :initials="auth()->user()->initials()"
                                />

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                    <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.item :href="route('profile.edit')" icon="user-circle" wire:navigate>
                        {{ __('Profil') }}
                    </flux:menu.item>
                    <flux:menu.item :href="route('security.edit')" icon="shield-check" wire:navigate>
                        {{ __('Keamanan Akun') }}
                    </flux:menu.item>

                    <flux:menu.separator />

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
        </flux:header>

        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
