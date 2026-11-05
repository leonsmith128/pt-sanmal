<x-layouts::app :title="__('Pusat Permohonan')">
    @php
        $activeFilterCount = collect([
            $filters['q'] ?? null,
            $filters['type'] ?? null,
            $filters['status'] ?? null,
            $filters['from'] ?? null,
            $filters['to'] ?? null,
        ])->filter(fn ($v) => filled($v))->count();
    @endphp

    <x-ui.page
        max-width="6xl"
        gap="5"
        x-data="{
            filterOpen: {{ $activeFilterCount > 0 ? 'true' : 'false' }}
        }"
    >
        <x-ui.page-header
            :title="__('Pusat Permohonan')"
            :description="__('Pantau izin, sakit, cuti, lembur, dan koreksi absensi dari satu tempat.')"
        >
            <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:items-center">
                <x-ui.button
                    href="{{ route('leave-requests.create') }}"
                    variant="primary"
                    icon="plus"
                    class="w-full justify-center sm:w-auto"
                >
                    {{ __('Buat permohonan') }}
                </x-ui.button>
                <div class="grid grid-cols-2 gap-2 sm:flex sm:items-center">
                    <x-ui.button
                        href="{{ route('attendance-corrections.create') }}"
                        variant="outline"
                        icon="clock"
                        class="w-full justify-center text-xs sm:w-auto sm:text-sm"
                    >
                        {{ __('Ajukan koreksi absensi') }}
                    </x-ui.button>
                    <x-ui.button
                        href="{{ route('overtime-requests.index') }}"
                        variant="ghost"
                        icon="clock"
                        class="w-full justify-center text-xs sm:w-auto sm:text-sm"
                    >
                        {{ __('Lembur') }}
                    </x-ui.button>
                </div>
            </div>
        </x-ui.page-header>

        <x-ui.flash-message />

        {{-- Status Pills & Filter Controls Toolbar --}}
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            {{-- Quick Status Filter Chips --}}
            <div class="flex flex-wrap items-center gap-1.5" role="group" aria-label="{{ __('Filter cepat status') }}">
                @php
                    $statusPills = [
                        '' => __('Semua'),
                        'pending' => __('Menunggu'),
                        'approved' => __('Disetujui'),
                        'rejected' => __('Ditolak'),
                        'cancelled' => __('Dibatalkan'),
                    ];
                @endphp
                @foreach ($statusPills as $statusValue => $statusLabel)
                    @php
                        $isActive = $filters['status'] === $statusValue;
                        $pillParams = array_merge(request()->except('page'), ['status' => $statusValue]);
                        if ($statusValue === '') {
                            unset($pillParams['status']);
                        }
                    @endphp
                    <a
                        href="{{ route('leave-requests.index', $pillParams) }}"
                        class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-medium transition-colors {{ $isActive ? 'bg-sanmal-brand-blue text-white shadow-xs dark:bg-sanmal-brand-yellow-dark dark:text-zinc-900' : 'bg-sanmal-surface-card text-sanmal-text-secondary hover:bg-sanmal-surface-secondary hover:text-sanmal-text-primary dark:bg-sanmal-surface-card-dark dark:text-sanmal-text-muted dark:hover:bg-sanmal-surface-elevated-dark shadow-xs' }}"
                    >
                        @if ($statusValue === 'pending')
                            <span class="size-1.5 rounded-full {{ $isActive ? 'bg-white dark:bg-zinc-900' : 'bg-amber-500' }}"></span>
                        @elseif ($statusValue === 'approved')
                            <span class="size-1.5 rounded-full {{ $isActive ? 'bg-white dark:bg-zinc-900' : 'bg-emerald-500' }}"></span>
                        @elseif ($statusValue === 'rejected')
                            <span class="size-1.5 rounded-full {{ $isActive ? 'bg-white dark:bg-zinc-900' : 'bg-red-500' }}"></span>
                        @endif
                        <span>{{ $statusLabel }}</span>
                    </a>
                @endforeach
            </div>

            {{-- Secondary controls: Filter toggle & Export --}}
            <div class="flex items-center justify-between gap-2 sm:justify-end">
                <span class="text-xs text-sanmal-text-muted sm:hidden">
                    {{ number_format($totalCount) }} {{ __('hasil') }}
                </span>
                <div class="flex items-center gap-2">
                    <x-ui.button
                        type="button"
                        variant="outline"
                        icon="funnel"
                        x-on:click="filterOpen = !filterOpen"
                        x-bind:aria-expanded="filterOpen.toString()"
                        aria-controls="leave-request-filter-panel"
                        class="whitespace-nowrap"
                    >
                        {{ __('Filter') }}
                        @if ($activeFilterCount > 0)
                            <span class="ml-1 inline-flex min-w-5 items-center justify-center rounded-full bg-sanmal-brand-blue/15 px-1.5 py-0.5 text-[11px] font-semibold tabular-nums text-sanmal-brand-blue dark:bg-sanmal-brand-yellow/20 dark:text-sanmal-brand-yellow">
                                {{ $activeFilterCount }}
                            </span>
                        @endif
                    </x-ui.button>
                    <x-ui.button
                        href="{{ route('leave-requests.export', request()->query()) }}"
                        variant="outline"
                        icon="arrow-down-tray"
                        class="whitespace-nowrap"
                        title="{{ __('Ekspor CSV') }}"
                    >
                        <span class="hidden sm:inline">{{ __('Ekspor CSV') }}</span>
                        <span class="sm:hidden">{{ __('Ekspor') }}</span>
                    </x-ui.button>
                </div>
            </div>
        </div>

        {{-- Collapsible Filter Panel --}}
        <x-ui.card
            padding="p-4 sm:p-5"
            x-show="filterOpen"
            x-cloak
            x-transition
            id="leave-request-filter-panel"
        >
            <div class="mb-4 flex items-center justify-between gap-3">
                <div>
                    <flux:heading size="lg">{{ __('Filter permohonan') }}</flux:heading>
                    <flux:text class="mt-1 text-sm">{{ __('Tanggal memfilter periode permohonan, bukan tanggal pengajuan.') }}</flux:text>
                </div>
                <span class="hidden shrink-0 text-sm text-sanmal-text-muted sm:inline">
                    {{ number_format($totalCount) }} {{ __('hasil') }}
                </span>
            </div>

            <form method="GET" action="{{ route('leave-requests.index') }}" class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-6">
                <div class="sm:col-span-2 lg:col-span-2">
                    <x-ui.input
                        name="q"
                        label="{{ __('Cari') }}"
                        :value="$filters['q']"
                        placeholder="{{ __('Nomor, jenis, atau alasan') }}"
                        icon="magnifying-glass"
                        clearable
                    />
                </div>
                <x-ui.select name="type" label="{{ __('Jenis') }}">
                    <flux:select.option value="">{{ __('Semua jenis') }}</flux:select.option>
                    <flux:select.option value="izin" :selected="$filters['type'] === 'izin'">{{ __('Izin') }}</flux:select.option>
                    <flux:select.option value="sakit" :selected="$filters['type'] === 'sakit'">{{ __('Sakit') }}</flux:select.option>
                    <flux:select.option value="cuti" :selected="$filters['type'] === 'cuti'">{{ __('Cuti') }}</flux:select.option>
                    <flux:select.option value="lembur" :selected="$filters['type'] === 'lembur'">{{ __('Lembur') }}</flux:select.option>
                    <flux:select.option value="koreksi_absensi" :selected="$filters['type'] === 'koreksi_absensi'">{{ __('Koreksi absensi') }}</flux:select.option>
                </x-ui.select>
                <x-ui.select name="status" label="{{ __('Status') }}">
                    <flux:select.option value="">{{ __('Semua status') }}</flux:select.option>
                    <flux:select.option value="pending" :selected="$filters['status'] === 'pending'">{{ __('Menunggu') }}</flux:select.option>
                    <flux:select.option value="approved" :selected="$filters['status'] === 'approved'">{{ __('Disetujui') }}</flux:select.option>
                    <flux:select.option value="rejected" :selected="$filters['status'] === 'rejected'">{{ __('Ditolak') }}</flux:select.option>
                    <flux:select.option value="cancelled" :selected="$filters['status'] === 'cancelled'">{{ __('Dibatalkan') }}</flux:select.option>
                </x-ui.select>
                <x-ui.input
                    name="from"
                    label="{{ __('Dari tanggal') }}"
                    type="date"
                    :value="$filters['from']"
                />
                <x-ui.input
                    name="to"
                    label="{{ __('Sampai tanggal') }}"
                    type="date"
                    :value="$filters['to']"
                />
                <div class="flex flex-col gap-2 sm:col-span-2 lg:col-span-6 sm:flex-row sm:justify-end">
                    <x-ui.button href="{{ route('leave-requests.index') }}" variant="ghost" class="w-full sm:w-auto">{{ __('Reset filter') }}</x-ui.button>
                    <x-ui.button type="submit" variant="primary" icon="funnel" class="w-full sm:w-auto">{{ __('Terapkan filter') }}</x-ui.button>
                    <x-ui.button href="{{ route('leave-requests.export', request()->query()) }}" variant="outline" icon="arrow-down-tray" class="w-full sm:w-auto">{{ __('Ekspor CSV') }}</x-ui.button>
                </div>
            </form>
        </x-ui.card>

        @if ($requests->isEmpty())
            <x-ui.card padding="px-5 py-12 text-center">
                <div class="mx-auto flex size-12 items-center justify-center rounded-full bg-sanmal-surface-secondary text-sanmal-text-muted dark:bg-sanmal-surface-inset-dark">
                    <flux:icon name="inbox" class="size-6" />
                </div>
                <flux:heading size="md" class="mt-4 font-semibold text-sanmal-text-primary">{{ __('Tidak ada permohonan yang cocok') }}</flux:heading>
                <flux:text class="mt-1 text-sm text-sanmal-text-secondary">
                    @if ($activeFilterCount > 0)
                        {{ __('Coba ubah filter atau buat permohonan baru.') }}
                    @else
                        {{ __('Anda belum memiliki pengajuan izin, sakit, cuti, lembur, atau koreksi absensi.') }}
                    @endif
                </flux:text>
                <div class="mt-5 flex flex-wrap items-center justify-center gap-2">
                    @if ($activeFilterCount > 0)
                        <x-ui.button href="{{ route('leave-requests.index') }}" variant="ghost" icon="x-mark">
                            {{ __('Hapus semua filter') }}
                        </x-ui.button>
                    @endif
                    <x-ui.button href="{{ route('leave-requests.create') }}" variant="primary" icon="plus">
                        {{ __('Buat permohonan baru') }}
                    </x-ui.button>
                </div>
            </x-ui.card>
        @else
            {{-- Mobile: cards avoid horizontal scrolling and keep primary status visible. --}}
            <div class="space-y-3 md:hidden">
                @foreach ($requests as $item)
                    @php
                        $typeIcon = match ($item->type) {
                            'cuti' => 'calendar-days',
                            'sakit' => 'heart',
                            'lembur' => 'clock',
                            'koreksi_absensi' => 'pencil-square',
                            default => 'document-text',
                        };
                        $statusColor = match ($item->status) {
                            'approved' => 'green',
                            'rejected' => 'red',
                            'cancelled' => 'zinc',
                            default => 'amber',
                        };
                    @endphp
                    <x-ui.card padding="p-4 sm:p-5">
                        <article class="space-y-3">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <span class="inline-flex items-center gap-1 font-mono text-xs font-semibold tracking-wider text-sanmal-text-muted">
                                        {{ $item->reference }}
                                    </span>
                                    <div class="mt-1 flex items-center gap-2">
                                        <span class="flex size-7 shrink-0 items-center justify-center rounded-lg bg-sanmal-surface-secondary text-sanmal-brand-blue dark:bg-sanmal-surface-inset-dark dark:text-sanmal-brand-blue-light">
                                            <flux:icon name="{{ $typeIcon }}" class="size-4" />
                                        </span>
                                        <h2 class="text-base font-semibold text-sanmal-text-primary">{{ $item->type_label }}</h2>
                                    </div>
                                    <p class="mt-1 text-xs text-sanmal-text-muted">
                                        {{ __('Diajukan') }}: {{ $item->submitted_at?->format('d/m/Y H:i') ?? '—' }}
                                    </p>
                                </div>
                                <x-ui.badge :color="$statusColor">{{ $item->status_label }}</x-ui.badge>
                            </div>

                            <div class="space-y-1.5 text-xs text-sanmal-text-secondary">
                                <div class="flex items-center gap-1.5">
                                    <flux:icon name="calendar" class="size-3.5 shrink-0 text-sanmal-text-muted" />
                                    <span class="font-medium text-sanmal-text-primary">{{ __('Periode') }}:</span>
                                    <span>
                                        {{ $item->period_start ? \Illuminate\Support\Carbon::parse($item->period_start)->format('d/m/Y') : '—' }}
                                        @if ($item->period_end && $item->period_end !== $item->period_start)
                                            &ndash; {{ \Illuminate\Support\Carbon::parse($item->period_end)->format('d/m/Y') }}
                                        @endif
                                    </span>
                                </div>
                            </div>

                            <div class="text-sm leading-relaxed text-sanmal-text-secondary">
                                <p class="whitespace-pre-line break-words">{{ $item->summary }}</p>
                                @if ($item->operational_status)
                                    <div class="mt-2 inline-flex items-center gap-1.5 rounded-md bg-sanmal-surface-secondary px-2.5 py-1 text-xs text-sanmal-text-secondary dark:bg-sanmal-surface-inset-dark">
                                        <flux:icon name="arrow-path" class="size-3.5 text-sanmal-brand-blue dark:text-sanmal-brand-blue-light" />
                                        <span><span class="font-medium text-sanmal-text-primary">{{ __('Proses lembur') }}:</span> {{ $item->operational_status }}</span>
                                    </div>
                                @endif
                            </div>

                            @if ($item->reviewer || $item->decision_note)
                                <div class="rounded-xl border border-sanmal-border/50 bg-sanmal-surface-inset/60 p-3 text-xs dark:border-sanmal-border-dark/40 dark:bg-sanmal-surface-inset-dark/70">
                                    <div class="flex items-center gap-1.5 font-medium text-sanmal-text-primary">
                                        <flux:icon name="user-check" class="size-3.5 text-sanmal-brand-blue dark:text-sanmal-brand-blue-light" />
                                        <span>{{ __('Diproses oleh') }}: {{ $item->reviewer ?? 'Admin' }}</span>
                                        @if ($item->decided_at)
                                            <span class="font-normal text-sanmal-text-muted">&middot; {{ $item->decided_at->format('d/m/Y H:i') }}</span>
                                        @endif
                                    </div>
                                    @if ($item->decision_note)
                                        <p class="mt-1.5 break-words pl-5 text-sanmal-text-secondary italic">{{ $item->decision_note }}</p>
                                    @endif
                                </div>
                            @endif

                            @if ($item->cancelable && $item->cancel_route)
                                <form method="POST" action="{{ $item->cancel_route }}" onsubmit="return confirm('Batalkan permohonan ini?')">
                                    @csrf @method('PATCH')
                                    <x-ui.button
                                        type="submit"
                                        variant="ghost"
                                        size="sm"
                                        icon="x-circle"
                                        class="w-full text-red-600 hover:bg-red-50 hover:text-red-700 dark:text-red-400 dark:hover:bg-red-950/30"
                                    >
                                        {{ __('Batalkan permohonan') }}
                                    </x-ui.button>
                                </form>
                            @endif
                        </article>
                    </x-ui.card>
                @endforeach
            </div>

            {{-- Desktop/tablet: comparable columns, with operational overtime status kept separate. --}}
            <x-ui.card padding="p-0" class="hidden overflow-hidden md:block">
                <div
                    class="overflow-x-auto overscroll-x-contain"
                    role="region"
                    aria-label="{{ __('Tabel permohonan; geser horizontal untuk melihat seluruh kolom') }}"
                    tabindex="0"
                >
                    <table class="min-w-[68rem] w-full text-left text-sm">
                        <thead class="sticky top-0 z-10 bg-sanmal-surface-secondary text-xs uppercase tracking-wide text-sanmal-text-muted shadow-xs dark:bg-sanmal-surface-secondary-dark dark:text-sanmal-text-muted">
                            <tr>
                                <th scope="col" class="px-4 py-3">
                                    <x-ui.button size="sm" variant="ghost" icon-trailing="{{ $filters['sort'] === 'reference' ? ($filters['direction'] === 'asc' ? 'arrow-up' : 'arrow-down') : 'arrows-up-down' }}" href="{{ route('leave-requests.index', array_merge(request()->except('page'), ['sort' => 'reference', 'direction' => $filters['sort'] === 'reference' && $filters['direction'] === 'asc' ? 'desc' : 'asc'])) }}">{{ __('Nomor / Jenis') }}</x-ui.button>
                                </th>
                                <th scope="col" class="px-4 py-3">
                                    <x-ui.button size="sm" variant="ghost" icon-trailing="{{ $filters['sort'] === 'submitted' ? ($filters['direction'] === 'asc' ? 'arrow-up' : 'arrow-down') : 'arrows-up-down' }}" href="{{ route('leave-requests.index', array_merge(request()->except('page'), ['sort' => 'submitted', 'direction' => $filters['sort'] === 'submitted' && $filters['direction'] === 'asc' ? 'desc' : 'asc'])) }}">{{ __('Tanggal pengajuan') }}</x-ui.button>
                                </th>
                                <th scope="col" class="px-4 py-3">
                                    <x-ui.button size="sm" variant="ghost" icon-trailing="{{ $filters['sort'] === 'period' ? ($filters['direction'] === 'asc' ? 'arrow-up' : 'arrow-down') : 'arrows-up-down' }}" href="{{ route('leave-requests.index', array_merge(request()->except('page'), ['sort' => 'period', 'direction' => $filters['sort'] === 'period' && $filters['direction'] === 'asc' ? 'desc' : 'asc'])) }}">{{ __('Periode') }}</x-ui.button>
                                </th>
                                <th scope="col" class="px-4 py-3">{{ __('Ringkasan') }}</th>
                                <th scope="col" class="px-4 py-3">
                                    <x-ui.button size="sm" variant="ghost" icon-trailing="{{ $filters['sort'] === 'status' ? ($filters['direction'] === 'asc' ? 'arrow-up' : 'arrow-down') : 'arrows-up-down' }}" href="{{ route('leave-requests.index', array_merge(request()->except('page'), ['sort' => 'status', 'direction' => $filters['sort'] === 'status' && $filters['direction'] === 'asc' ? 'desc' : 'asc'])) }}">{{ __('Status') }}</x-ui.button>
                                </th>
                                <th scope="col" class="px-4 py-3">{{ __('Keputusan') }}</th>
                                <th scope="col" class="px-4 py-3 text-right">{{ __('Aksi') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-sanmal-border dark:divide-sanmal-border-dark">
                            @foreach ($requests as $item)
                                <tr class="align-top transition-colors hover:bg-sanmal-surface-secondary/40 dark:hover:bg-sanmal-surface-elevated-dark/20">
                                    <td class="px-4 py-4">
                                        <p class="font-mono text-xs text-sanmal-text-muted">{{ $item->reference }}</p>
                                        <p class="mt-1 font-semibold text-sanmal-text-primary">{{ $item->type_label }}</p>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-4 font-mono text-xs text-sanmal-text-muted">{{ $item->submitted_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                    <td class="whitespace-nowrap px-4 py-4 text-sm font-medium text-sanmal-text-primary">
                                        {{ $item->period_start ? \Illuminate\Support\Carbon::parse($item->period_start)->format('d/m/Y') : '—' }}
                                        @if ($item->period_end && $item->period_end !== $item->period_start)
                                            <span class="font-normal text-sanmal-text-muted">&ndash; {{ \Illuminate\Support\Carbon::parse($item->period_end)->format('d/m/Y') }}</span>
                                        @endif
                                    </td>
                                    <td class="max-w-sm px-4 py-4">
                                        <p class="whitespace-pre-line break-words text-sm text-sanmal-text-secondary">{{ $item->summary }}</p>
                                        @if ($item->operational_status)
                                            <p class="mt-1.5 inline-flex items-center gap-1 rounded bg-sanmal-surface-secondary px-2 py-0.5 text-xs text-sanmal-text-secondary dark:bg-sanmal-surface-inset-dark">
                                                <span class="font-medium text-sanmal-text-primary">{{ __('Proses lembur') }}:</span> {{ $item->operational_status }}
                                            </p>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-4">
                                        <x-ui.badge :color="match ($item->status) {'approved' => 'green', 'rejected' => 'red', 'cancelled' => 'zinc', default => 'amber'}">{{ $item->status_label }}</x-ui.badge>
                                    </td>
                                    <td class="min-w-48 px-4 py-4 text-xs">
                                        @if ($item->reviewer)
                                            <p class="font-medium text-sanmal-text-primary">{{ $item->reviewer }}</p>
                                        @endif
                                        @if ($item->decided_at)
                                            <p class="text-sanmal-text-muted">{{ $item->decided_at->format('d/m/Y H:i') }}</p>
                                        @endif
                                        @if ($item->decision_note)
                                            <p class="mt-1 whitespace-pre-line break-words text-sanmal-text-secondary italic">{{ $item->decision_note }}</p>
                                        @endif
                                        @if (! $item->reviewer && ! $item->decision_note)
                                            <span class="text-sanmal-text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-4 text-right">
                                        @if ($item->cancelable && $item->cancel_route)
                                            <form method="POST" action="{{ $item->cancel_route }}" onsubmit="return confirm('Batalkan permohonan ini?')">
                                                @csrf @method('PATCH')
                                                <x-ui.button
                                                    type="submit"
                                                    variant="ghost"
                                                    size="sm"
                                                    class="text-red-600 hover:bg-red-50 hover:text-red-700 dark:text-red-400 dark:hover:bg-red-950/30"
                                                >
                                                    {{ __('Batalkan') }}
                                                </x-ui.button>
                                            </form>
                                        @else
                                            <span class="text-sanmal-text-muted">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-ui.card>
            <div>{{ $requests->links() }}</div>
        @endif
    </x-ui.page>
</x-layouts::app>
