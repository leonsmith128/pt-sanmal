<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        @php
            $sidebarUser = auth()->user();
            $sidebarEmployee = $sidebarUser->employee;
            $isManager = $sidebarUser->isManager();
            $isEmployee = $sidebarUser->isEmployee();

            $unseenRequestDecisions = $sidebarEmployee
                ? $sidebarEmployee->leaveRequests()->whereIn('status', ['approved', 'rejected'])->whereNull('decision_seen_at')->count()
                    + $sidebarEmployee->overtimeRequests()->whereIn('approval_status', ['approved', 'rejected'])->whereNull('decision_seen_at')->count()
                    + $sidebarEmployee->attendanceCorrections()->whereIn('status', ['approved', 'rejected'])->whereNull('decision_seen_at')->count()
                : 0;
        @endphp

        <flux:sidebar
            sticky
            :collapsible="$isManager"
            class="h-screen min-h-screen overflow-x-hidden border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 {{ $isEmployee && ! $isManager ? 'hidden lg:flex' : '' }}"
        >
            <flux:sidebar.header>
                <div class="flex w-full items-center justify-between gap-2">
                    <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate class="in-data-flux-sidebar-collapsed-desktop:hidden" />
                    <flux:dropdown position="bottom" align="end" class="in-data-flux-sidebar-collapsed-desktop:hidden" data-test="sidebar-theme-menu">
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
                @if ($isManager)
                    <flux:sidebar.collapse class="!opacity-100" />
                @endif
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.group :heading="$isEmployee ? __('Navigasi') : __('Platform')" class="grid">
                    <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                        {{ $isEmployee ? __('Beranda') : __('Dashboard') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>

                @if ($isManager)
                    @if ($sidebarEmployee)
                        <flux:sidebar.group
                            icon="user-circle"
                            :heading="__('Aktivitas Saya')"
                            :expanded="request()->routeIs('attendance', 'attendance-history.*', 'leave-requests.*', 'overtime-requests.*')"
                            class="grid"
                        >
                            <flux:sidebar.item icon="clock" :href="route('attendance')" :current="request()->routeIs('attendance')" wire:navigate>
                                {{ __('Absensi Saya') }}
                            </flux:sidebar.item>
                            <flux:sidebar.item icon="calendar" :href="route('attendance-history.index')" :current="request()->routeIs('attendance-history.*')" wire:navigate>
                                {{ __('Riwayat Absensi Saya') }}
                            </flux:sidebar.item>
                            <flux:sidebar.item
                                icon="clipboard-document-list"
                                :href="route('leave-requests.index')"
                                :current="request()->routeIs('leave-requests.*')"
                                :badge="$unseenRequestDecisions > 0 ? $unseenRequestDecisions : null"
                                wire:navigate
                            >
                                {{ __('Pusat Permohonan Saya') }}
                            </flux:sidebar.item>
                            <flux:sidebar.item icon="check" :href="route('overtime-requests.index') . '#riwayat-lembur'" :current="request()->routeIs('overtime-requests.*')" wire:navigate>
                                {{ __('Riwayat Lembur Saya') }}
                            </flux:sidebar.item>
                        </flux:sidebar.group>
                    @endif

                    <flux:sidebar.group
                        icon="clipboard-document-list"
                        :heading="__('Operasional')"
                        :expanded="request()->routeIs('admin.kehadiran', 'admin.daily-attendance*', 'admin.projects.*')"
                        class="grid"
                    >
                        <flux:sidebar.item icon="clipboard-document-list" :href="route('admin.kehadiran')" :current="request()->routeIs('admin.kehadiran')" wire:navigate>
                            {{ __('Kehadiran & Aktivitas Pegawai') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="calendar-days" :href="route('admin.daily-attendance')" :current="request()->routeIs('admin.daily-attendance*')" wire:navigate>
                            {{ __('Rekap Harian') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="briefcase" :href="route('admin.projects.index')" :current="request()->routeIs('admin.projects.*')" wire:navigate>
                            {{ __('Pengelolaan Proyek') }}
                        </flux:sidebar.item>
                    </flux:sidebar.group>

                    <flux:sidebar.group
                        icon="building-office-2"
                        :heading="__('Master Data')"
                        :expanded="request()->routeIs('admin.clients.*', 'admin.locations.*', 'admin.employees.*')"
                        class="grid"
                    >
                        <flux:sidebar.item icon="building-office-2" :href="route('admin.clients.index')" :current="request()->routeIs('admin.clients.*', 'admin.locations.*')" wire:navigate>
                            {{ __('Master Data Client') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="users" :href="route('admin.employees.index')" :current="request()->routeIs('admin.employees.*')" wire:navigate>
                            {{ __('Daftar & Akun Pegawai') }}
                        </flux:sidebar.item>
                    </flux:sidebar.group>

                    <flux:sidebar.group
                        icon="calendar"
                        :heading="__('Permohonan')"
                        :expanded="request()->routeIs('admin.leave-requests.*', 'admin.overtime-requests.*', 'admin.attendance-corrections.*')"
                        class="grid"
                    >
                        <flux:sidebar.item icon="calendar" :href="route('admin.leave-requests.index')" :current="request()->routeIs('admin.leave-requests.*')" wire:navigate>
                            {{ __('Izin, Sakit & Cuti') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="clock" :href="route('admin.overtime-requests.index')" :current="request()->routeIs('admin.overtime-requests.*')" wire:navigate>
                            {{ __('Permohonan Lembur') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="document-check" :href="route('admin.attendance-corrections.index')" :current="request()->routeIs('admin.attendance-corrections.*')" wire:navigate>
                            {{ __('Permohonan Koreksi Absensi') }}
                        </flux:sidebar.item>
                    </flux:sidebar.group>
                @elseif ($sidebarEmployee)
                    <flux:sidebar.group :heading="__('Aktivitas Pegawai')" class="grid">
                        <flux:sidebar.item icon="clock" :href="route('attendance')" :current="request()->routeIs('attendance')" wire:navigate>
                            {{ __('Absensi') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="calendar" :href="route('attendance-history.index')" :current="request()->routeIs('attendance-history.*')" wire:navigate>
                            {{ __('Riwayat Absensi') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item
                            icon="clipboard-document-list"
                            :href="route('leave-requests.index')"
                            :current="request()->routeIs('leave-requests.*')"
                            :badge="$unseenRequestDecisions > 0 ? $unseenRequestDecisions : null"
                            wire:navigate
                        >
                            {{ __('Pusat Permohonan') }}
                        </flux:sidebar.item>
                    </flux:sidebar.group>
                @endif
            </flux:sidebar.nav>

            <flux:spacer />

            <x-desktop-user-menu class="hidden lg:block" :name="$sidebarUser->name" />
        </flux:sidebar>

        @if ($isManager)
            <flux:header class="lg:hidden">
                <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />
                <flux:spacer />
                <x-app-logo href="{{ route('dashboard') }}" wire:navigate />
                <flux:spacer />
                <flux:dropdown position="top" align="end">
                    <flux:profile
                        :initials="$sidebarUser->initials()"
                        :avatar="$sidebarUser->profilePhotoUrl()"
                        icon-trailing="chevron-down"
                    />
                    <flux:menu>
                        <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                            <flux:avatar :name="$sidebarUser->name" :initials="$sidebarUser->initials()" />
                            <div class="grid flex-1 text-start text-sm leading-tight">
                                <flux:heading class="truncate">{{ $sidebarUser->name }}</flux:heading>
                                <flux:text class="truncate">{{ $sidebarUser->email }}</flux:text>
                            </div>
                        </div>
                        <flux:menu.separator />
                        <flux:menu.item :href="route('profile.edit')" icon="user-circle" wire:navigate>{{ __('Profil') }}</flux:menu.item>
                        <flux:menu.item :href="route('security.edit')" icon="shield-check" wire:navigate>{{ __('Keamanan Akun') }}</flux:menu.item>
                        <flux:menu.separator />
                        <form method="POST" action="{{ route('logout') }}" class="w-full">
                            @csrf
                            <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full cursor-pointer" data-test="logout-button">
                                {{ __('Log out') }}
                            </flux:menu.item>
                        </form>
                    </flux:menu>
                </flux:dropdown>
            </flux:header>
        @elseif ($isEmployee)
            <flux:header class="lg:hidden">
                <x-app-logo href="{{ route('dashboard') }}" wire:navigate />
                <flux:spacer />
                <flux:dropdown position="bottom" align="end">
                    <flux:profile
                        :initials="$sidebarUser->initials()"
                        :avatar="$sidebarUser->profilePhotoUrl()"
                        icon-trailing="chevron-down"
                    />
                    <flux:menu>
                        <flux:menu.item :href="route('profile.edit')" icon="user-circle" wire:navigate>{{ __('Profil') }}</flux:menu.item>
                        <flux:menu.item :href="route('security.edit')" icon="shield-check" wire:navigate>{{ __('Keamanan Akun') }}</flux:menu.item>
                        <flux:menu.separator />
                        <form method="POST" action="{{ route('logout') }}" class="w-full">
                            @csrf
                            <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full cursor-pointer" data-test="logout-button">
                                {{ __('Log out') }}
                            </flux:menu.item>
                        </form>
                    </flux:menu>
                </flux:dropdown>
            </flux:header>

            <nav
                class="fixed inset-x-0 bottom-0 z-40 border-t border-zinc-200 bg-white pb-[env(safe-area-inset-bottom)] dark:border-zinc-700 dark:bg-zinc-900 lg:hidden"
                aria-label="{{ __('Navigasi utama') }}"
            >
                <div class="mx-auto flex min-h-16 max-w-xl items-stretch justify-around px-2">
                    <a href="{{ route('dashboard') }}" wire:navigate @class(['flex min-w-0 flex-1 flex-col items-center justify-center gap-1 px-1 py-2 text-[11px] font-medium', 'text-accent' => request()->routeIs('dashboard'), 'text-zinc-500 dark:text-zinc-400' => ! request()->routeIs('dashboard')]) @if (request()->routeIs('dashboard')) aria-current="page" @endif>
                        <flux:icon name="home" class="size-5" />
                        <span>{{ __('Beranda') }}</span>
                    </a>
                    <a href="{{ route('attendance') }}" wire:navigate @class(['flex min-w-0 flex-1 flex-col items-center justify-center gap-1 px-1 py-2 text-[11px] font-medium', 'text-accent' => request()->routeIs('attendance'), 'text-zinc-500 dark:text-zinc-400' => ! request()->routeIs('attendance')]) @if (request()->routeIs('attendance')) aria-current="page" @endif>
                        <flux:icon name="clock" class="size-5" />
                        <span>{{ __('Absensi') }}</span>
                    </a>
                    <a href="{{ route('attendance-history.index') }}" wire:navigate @class(['flex min-w-0 flex-1 flex-col items-center justify-center gap-1 px-1 py-2 text-[11px] font-medium', 'text-accent' => request()->routeIs('attendance-history.*'), 'text-zinc-500 dark:text-zinc-400' => ! request()->routeIs('attendance-history.*')]) @if (request()->routeIs('attendance-history.*')) aria-current="page" @endif>
                        <flux:icon name="calendar-days" class="size-5" />
                        <span>{{ __('Riwayat') }}</span>
                    </a>
                    <a href="{{ route('leave-requests.index') }}" wire:navigate @class(['flex min-w-0 flex-1 flex-col items-center justify-center gap-1 px-1 py-2 text-[11px] font-medium', 'text-accent' => request()->routeIs('leave-requests.*', 'overtime-requests.*', 'attendance-corrections.*'), 'text-zinc-500 dark:text-zinc-400' => ! request()->routeIs('leave-requests.*', 'overtime-requests.*', 'attendance-corrections.*')]) @if (request()->routeIs('leave-requests.*', 'overtime-requests.*', 'attendance-corrections.*')) aria-current="page" @endif>
                        <flux:icon name="clipboard-document-list" class="size-5" />
                        <span>{{ __('Pengajuan') }}</span>
                    </a>
                    <a href="{{ route('profile.edit') }}" wire:navigate @class(['flex min-w-0 flex-1 flex-col items-center justify-center gap-1 px-1 py-2 text-[11px] font-medium', 'text-accent' => request()->routeIs('profile.edit', 'security.edit'), 'text-zinc-500 dark:text-zinc-400' => ! request()->routeIs('profile.edit', 'security.edit')]) @if (request()->routeIs('profile.edit', 'security.edit')) aria-current="page" @endif>
                        <flux:icon name="user-circle" class="size-5" />
                        <span>{{ __('Profil') }}</span>
                    </a>
                </div>
            </nav>
        @endif

        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
