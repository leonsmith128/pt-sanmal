<x-layouts::app :title="'Persetujuan Permohonan'">
    <x-ui.page class="!max-w-6xl !gap-6">
        <x-admin.page-header
            :title="__('Persetujuan Permohonan')"
            :description="__('Admin dan superadmin menggunakan alur persetujuan yang sama.')"
        >
            <x-ui.button href="{{ route('admin.daily-attendance') }}" variant="outline" icon="calendar-days">
                {{ __('Rekap harian') }}
            </x-ui.button>
        </x-admin.page-header>

        <x-admin.flash-message />

        @php
            $statusPills = ['' => __('Semua')] + collect(\App\Enums\LeaveRequestStatus::cases())
                ->mapWithKeys(fn ($status) => [$status->value => $status->label()])
                ->all();
        @endphp

        <div class="flex flex-wrap items-center gap-1.5" role="group" aria-label="{{ __('Filter cepat status permohonan') }}">
            @foreach ($statusPills as $statusValue => $statusLabel)
                @php
                    $isActive = (string) request('status', '') === (string) $statusValue;
                    $pillParams = array_merge(request()->except('page'), ['status' => $statusValue]);
                    if ($statusValue === '') {
                        unset($pillParams['status']);
                    }
                @endphp
                <a
                    href="{{ route('admin.leave-requests.index', $pillParams) }}"
                    @if ($isActive) aria-current="page" @endif
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



        <div class="space-y-3 md:hidden">
            @forelse ($requests as $item)
                <x-ui.card padding="p-4">
                    <article class="space-y-3">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h2 class="break-words font-semibold text-[var(--sanmal-text-primary)]">
                                    {{ $item->employee?->user?->name ?? 'Pegawai #'.$item->employee_id }}
                                </h2>
                                <p class="mt-1 text-sm text-[var(--sanmal-text-secondary)]">{{ $item->leaveType?->name ?? '—' }}</p>
                            </div>
                            <x-admin.request-status-badge :status="$item->status" />
                        </div>
                        <dl class="grid grid-cols-2 gap-3 border-t border-sanmal-border pt-3 text-sm dark:border-sanmal-border-dark">
                            <x-ui.metadata-item :label="__('Periode')" value-class="mt-0.5 font-medium tabular-nums">
                                {{ $item->start_date->format('d/m/Y') }} – {{ $item->end_date->format('d/m/Y') }}
                            </x-ui.metadata-item>
                            <x-ui.metadata-item :label="__('Diajukan')" value-class="mt-0.5 font-mono text-xs text-[var(--sanmal-text-muted)]">
                                {{ $item->created_at->format('d/m/Y H:i') }}
                            </x-ui.metadata-item>
                        </dl>
                        <div class="flex justify-end">
                            <x-ui.button href="{{ route('admin.leave-requests.show', $item) }}" size="sm" variant="outline">
                                {{ __('Buka detail') }}
                            </x-ui.button>
                        </div>
                    </article>
                </x-ui.card>
            @empty
                <x-ui.empty-state :message="__('Tidak ada permohonan pada filter ini.')" />
            @endforelse
        </div>

        <x-ui.card padding="p-0" class="hidden overflow-hidden md:block">
            <div class="overflow-x-auto" role="region" aria-label="{{ __('Daftar permohonan; geser horizontal untuk melihat seluruh kolom') }}" tabindex="0">
                <table class="w-full text-left text-sm">
                    <thead class="bg-sanmal-surface-secondary text-xs uppercase tracking-wide text-[var(--sanmal-text-muted)] dark:bg-sanmal-surface-secondary-dark">
                        <tr>
                            <th class="px-4 py-3">{{ __('Pegawai') }}</th>
                            <th class="px-4 py-3">{{ __('Jenis') }}</th>
                            <th class="px-4 py-3">{{ __('Periode') }}</th>
                            <th class="px-4 py-3">{{ __('Diajukan') }}</th>
                            <th class="px-4 py-3">{{ __('Status') }}</th>
                            <th class="px-4 py-3 text-right">{{ __('Detail') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-sanmal-border dark:divide-sanmal-border-dark">
                        @forelse ($requests as $item)
                            <tr class="transition-colors hover:bg-sanmal-surface-secondary/40 dark:hover:bg-sanmal-surface-elevated-dark/20">
                                <td class="px-4 py-3 font-medium text-[var(--sanmal-text-primary)]">
                                    {{ $item->employee?->user?->name ?? 'Pegawai #'.$item->employee_id }}
                                </td>
                                <td class="px-4 py-3 text-[var(--sanmal-text-secondary)]">{{ $item->leaveType?->name ?? '—' }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-[var(--sanmal-text-secondary)]">
                                    {{ $item->start_date->format('d/m/Y') }} &ndash; {{ $item->end_date->format('d/m/Y') }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 font-mono text-xs text-[var(--sanmal-text-muted)]">
                                    {{ $item->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <x-admin.request-status-badge :status="$item->status" />
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-right">
                                    <x-ui.button href="{{ route('admin.leave-requests.show', $item) }}" size="sm" variant="outline">
                                        {{ __('Buka') }}
                                    </x-ui.button>
                                </td>
                            </tr>
                        @empty
                            <x-admin.empty-state :colspan="6" :message="__('Tidak ada permohonan pada filter ini.')" />
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-ui.card>

        <div class="mt-4">
            {{ $requests->links() }}
        </div>
    </x-ui.page>
</x-layouts::app>
