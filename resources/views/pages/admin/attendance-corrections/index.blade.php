<x-layouts::app :title="__('Koreksi Absensi')">
    <x-ui.page class="!max-w-6xl !gap-5">
        <x-admin.page-header
            :title="__('Pengelolaan Koreksi Absensi')"
            :description="__('Tinjau permohonan perubahan catatan clock-in atau clock-out.')"
        />

        <x-admin.flash-message />

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
                    $isActive = request('status', '') === $statusValue;
                    $pillParams = array_merge(request()->except('page'), ['status' => $statusValue]);
                    if ($statusValue === '') {
                        unset($pillParams['status']);
                    }
                @endphp
                <a
                    href="{{ route('admin.attendance-corrections.index', $pillParams) }}"
                    aria-current="{{ $isActive ? 'page' : 'false' }}"
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

        <x-ui.card padding="p-0" class="overflow-hidden">
            <div class="divide-y divide-sanmal-border dark:divide-sanmal-border-dark">
                @forelse ($corrections as $correction)
                    <a href="{{ route('admin.attendance-corrections.show', $correction) }}" class="flex flex-wrap items-center justify-between gap-3 p-4 transition-colors hover:bg-sanmal-surface-secondary/50 dark:hover:bg-sanmal-surface-elevated-dark/30">
                        <div>
                            <p class="font-medium text-[var(--sanmal-text-primary)]">{{ $correction->employee->user->name }}</p>
                            <p class="mt-0.5 text-sm text-[var(--sanmal-text-secondary)]">
                                {{ $correction->attendance_date->format('d M Y') }} ·
                                <span class="uppercase font-medium text-[var(--sanmal-text-primary)]">{{ str_replace('_', ' ', $correction->requested_type) }}</span> ·
                                <span class="font-mono text-xs text-[var(--sanmal-text-muted)]">{{ $correction->requested_recorded_at->format('H:i') }}</span>
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center rounded-md border border-sanmal-border px-2.5 py-1 text-xs font-medium text-[var(--sanmal-text-secondary)] dark:border-sanmal-border-dark">{{ __('Tinjau') }}</span>
                            <x-ui.badge :color="match($correction->status) { 'pending' => 'amber', 'approved' => 'green', 'rejected' => 'red', 'cancelled' => 'zinc', default => 'zinc' }">
                                {{ match($correction->status) { 'pending' => 'Menunggu', 'approved' => 'Disetujui', 'rejected' => 'Ditolak', 'cancelled' => 'Dibatalkan', default => ucfirst($correction->status) } }}
                            </x-ui.badge>
                        </div>
                    </a>
                @empty
                    <x-admin.empty-state
                        class="!rounded-none !border-0 !bg-transparent !p-8"
                        :message="__('Tidak ada permohonan koreksi.')"
                    />
                @endforelse
            </div>
        </x-ui.card>

        {{ $corrections->links() }}
    </x-ui.page>
</x-layouts::app>
