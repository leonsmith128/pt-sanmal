<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-dvh bg-sanmal-surface-page dark:bg-sanmal-surface-page-dark">
        @php
            $sidebarUser = auth()->user();
            $sidebarEmployee = $sidebarUser->employee;
            $isManager = $sidebarUser->isManager();
            $isEmployee = $sidebarUser->isEmployee();
            $unreadSystemNotifications = $sidebarUser->unreadNotifications()->count();
            $latestSystemNotifications = $sidebarUser->notifications()->latest()->limit(5)->get();

            $unseenRequestDecisions = $sidebarEmployee
                ? $sidebarEmployee->leaveRequests()->whereIn('status', ['approved', 'rejected'])->whereNull('decision_seen_at')->count()
                    + $sidebarEmployee->overtimeRequests()->whereIn('approval_status', ['approved', 'rejected'])->whereNull('decision_seen_at')->count()
                    + $sidebarEmployee->attendanceCorrections()->whereIn('status', ['approved', 'rejected'])->whereNull('decision_seen_at')->count()
                : 0;
        @endphp

        <flux:sidebar
            sticky
            :collapsible="$isManager ? true : 'mobile'"
            class="h-dvh min-h-dvh overflow-x-hidden bg-sanmal-surface-sidebar dark:bg-sanmal-surface-page-dark"
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

            <flux:sidebar.nav aria-label="{{ __('Navigasi utama') }}">
                @if ($isEmployee)
                    <div class="flex flex-col">
                        <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                            {{ __('Beranda') }}
                        </flux:sidebar.item>
                    </div>
                @else
                    <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                        {{ __('Dashboard') }}
                    </flux:sidebar.item>
                @endif
                @if ($isManager)
                    @if ($sidebarEmployee)
                        <x-ui.sidebar-section :heading="__('Aktivitas Saya')">
                            <flux:sidebar.item icon="home" :href="route('my-dashboard')" :current="request()->routeIs('my-dashboard')" wire:navigate>
                                {{ __('Dashboard Pribadi') }}
                            </flux:sidebar.item>
                            <flux:sidebar.item icon="clock" :href="route('attendance')" :current="request()->routeIs('attendance')" wire:navigate>
                                {{ __('Absensi') }}
                            </flux:sidebar.item>
                            <flux:sidebar.item icon="calendar" :href="route('attendance-history.index')" :current="request()->routeIs('attendance-history.*')" wire:navigate>
                                {{ __('Riwayat') }}
                            </flux:sidebar.item>
                            <flux:sidebar.item
                                icon="clipboard-document-list"
                                :href="route('leave-requests.index')"
                                :current="request()->routeIs('leave-requests.*')"
                                :badge="$unseenRequestDecisions > 0 ? $unseenRequestDecisions : null"
                                wire:navigate
                            >
                                {{ __('Permohonan Saya') }}
                            </flux:sidebar.item>
                            <flux:sidebar.item icon="clock" :href="route('overtime-requests.index') . '#riwayat-lembur'" :current="request()->routeIs('overtime-requests.*')" wire:navigate>
                                {{ __('Lembur Saya') }}
                            </flux:sidebar.item>
                        </x-ui.sidebar-section>
                    @endif

                    <x-ui.sidebar-section :heading="__('Operasional')">
                        <flux:sidebar.item icon="clipboard-document-list" :href="route('admin.kehadiran')" :current="request()->routeIs('admin.kehadiran')" wire:navigate>
                            {{ __('Kehadiran') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="calendar-days" :href="route('admin.daily-attendance')" :current="request()->routeIs('admin.daily-attendance*')" wire:navigate>
                            {{ __('Rekap Harian') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="briefcase" :href="route('admin.projects.index')" :current="request()->routeIs('admin.projects.*')" wire:navigate>
                            {{ __('Proyek') }}
                        </flux:sidebar.item>
                    </x-ui.sidebar-section>

                    <x-ui.sidebar-section :heading="__('Persetujuan')">
                        <flux:sidebar.item icon="calendar" :href="route('admin.leave-requests.index')" :current="request()->routeIs('admin.leave-requests.*')" wire:navigate>
                            {{ __('Izin & Cuti') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="clock" :href="route('admin.overtime-requests.index')" :current="request()->routeIs('admin.overtime-requests.*')" wire:navigate>
                            {{ __('Lembur') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="document-check" :href="route('admin.attendance-corrections.index')" :current="request()->routeIs('admin.attendance-corrections.*')" wire:navigate>
                            {{ __('Koreksi') }}
                        </flux:sidebar.item>
                    </x-ui.sidebar-section>

                    <x-ui.sidebar-section :heading="__('Data Master')">
                        <flux:sidebar.item icon="building-office-2" :href="route('admin.clients.index')" :current="request()->routeIs('admin.clients.*', 'admin.locations.*')" wire:navigate>
                            {{ __('Klien') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="users" :href="route('admin.employees.index')" :current="request()->routeIs('admin.employees.*')" wire:navigate>
                            {{ __('Pegawai') }}
                        </flux:sidebar.item>
                    </x-ui.sidebar-section>

                    <x-ui.sidebar-section :heading="__('Sistem')">
                        <flux:sidebar.item icon="clipboard-document-list" :href="route('admin.audit-logs.index')" :current="request()->routeIs('admin.audit-logs.*')" wire:navigate>
                            {{ __('Audit Log') }}
                        </flux:sidebar.item>
                    </x-ui.sidebar-section>
                @elseif ($sidebarEmployee)
                    <flux:sidebar.item icon="clock" :href="route('attendance')" :current="request()->routeIs('attendance')" wire:navigate>
                        {{ __('Absensi') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="calendar" :href="route('attendance-history.index')" :current="request()->routeIs('attendance-history.*')" wire:navigate>
                        {{ __('Riwayat') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item
                        icon="clipboard-document-list"
                        :href="route('leave-requests.index')"
                        :current="request()->routeIs('leave-requests.*')"
                        :badge="$unseenRequestDecisions > 0 ? $unseenRequestDecisions : null"
                        wire:navigate
                    >
                        {{ __('Permohonan') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="clock" :href="route('overtime-requests.index') . '#riwayat-lembur'" :current="request()->routeIs('overtime-requests.*')" wire:navigate>
                        {{ __('Lembur') }}
                    </flux:sidebar.item>
                @endif
            </flux:sidebar.nav>

            <flux:spacer />

            <x-desktop-user-menu class="hidden lg:block" :name="$sidebarUser->name" />
        </flux:sidebar>

        @if ($isManager)
            <flux:header class="border-b border-sanmal-border bg-sanmal-surface-primary dark:border-sanmal-border-dark dark:bg-sanmal-surface-primary-dark lg:hidden">
                <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />
                <x-app-logo href="{{ route('dashboard') }}" wire:navigate />
                <flux:spacer />
                <div class="flex shrink-0 items-center gap-1">
                    <flux:dropdown position="top" align="start">
                    <flux:profile
                        :initials="$sidebarUser->initials()"
                        :avatar="$sidebarUser->profilePhotoUrl()"
                        :chevron="false"
                        class="rounded-full [&_img]:rounded-full"
                    />
                    <flux:menu>
                        <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                            <x-ui.avatar :name="$sidebarUser->name" :initials="$sidebarUser->initials()" size="sm" />
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
                <x-ui.notification-dropdown
                    :notifications="$latestSystemNotifications"
                    :unread-count="$unreadSystemNotifications"
                />
                </div>
            </flux:header>
        @elseif ($isEmployee)
            <flux:header class="border-b border-sanmal-border bg-sanmal-surface-primary dark:border-sanmal-border-dark dark:bg-sanmal-surface-primary-dark lg:hidden">
                <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />
                <x-app-logo href="{{ route('dashboard') }}" wire:navigate />
                <flux:spacer />
                <div class="flex shrink-0 items-center gap-1">                <flux:dropdown position="bottom" align="start">
                    <flux:profile
                        :initials="$sidebarUser->initials()"
                        :avatar="$sidebarUser->profilePhotoUrl()"
                        :chevron="false"
                        class="rounded-full [&_img]:rounded-full"
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
                <x-ui.notification-dropdown
                    :notifications="$latestSystemNotifications"
                    :unread-count="$unreadSystemNotifications"
                />
                </div>
            </flux:header>

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
