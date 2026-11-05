<x-layouts::app :title="__('Pengelolaan Lembur')">
    <x-ui.page class="!max-w-6xl !gap-6">
        <x-admin.page-header
            :title="__('Pengelolaan lembur')"
            :description="__('Tinjau pengajuan, status konfirmasi foto, dan keputusan lembur pegawai.')"
        />

        <x-admin.flash-message />

        <div class="flex flex-wrap items-center gap-1.5" role="group" aria-label="{{ __('Filter cepat status persetujuan') }}">
            @php
                $statusPills = [
                    '' => __('Semua'),
                    'pending' => __('Menunggu'),
                    'approved' => __('Disetujui'),
                    'rejected' => __('Ditolak'),
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
                    href="{{ route('admin.overtime-requests.index', $pillParams) }}"
                    @class([
                        'inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-medium transition-colors shadow-xs',
                        'bg-sanmal-brand-blue text-white dark:bg-sanmal-brand-yellow-dark dark:text-zinc-900' => $isActive,
                        'bg-sanmal-surface-card text-sanmal-text-secondary hover:bg-sanmal-surface-secondary hover:text-sanmal-text-primary dark:bg-sanmal-surface-card-dark dark:text-sanmal-text-muted dark:hover:bg-sanmal-surface-elevated-dark' => ! $isActive,
                    ])
                    @if ($isActive) aria-current="page" @endif
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
            @forelse ($requests as $request)
                <x-ui.card padding="p-4">
                    <article class="space-y-3">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h2 class="break-words font-semibold text-[var(--sanmal-text-primary)]">{{ $request->employee->user->name }}</h2>
                                <p class="mt-1 text-sm tabular-nums text-[var(--sanmal-text-secondary)]">{{ $request->scheduledStartLocal()->format('d M Y, H:i') }}</p>
                            </div>
                            <x-ui.badge :color="$request->approval_status->badgeColor()">{{ $request->approval_status->label() }}</x-ui.badge>
                        </div>
                        <p class="text-sm text-[var(--sanmal-text-secondary)]">{{ Str::limit($request->reason ?? 'Alasan tidak dicantumkan', 110) }}</p>
                        <div class="flex flex-wrap items-center justify-between gap-2 border-t border-sanmal-border pt-3 dark:border-sanmal-border-dark">
                            <x-ui.badge :color="$request->state->badgeColor()">{{ $request->state->label() }}</x-ui.badge>
                            <x-ui.button href="{{ route('admin.overtime-requests.show', $request) }}" size="sm" variant="outline">
                                {{ __('Tinjau') }}
                            </x-ui.button>
                        </div>
                    </article>
                </x-ui.card>
            @empty
                <x-ui.empty-state :message="__('Tidak ada pengajuan lembur yang cocok.')" />
            @endforelse
        </div>

        <x-ui.card padding="p-0" class="hidden overflow-hidden md:block">
            <div class="overflow-x-auto" role="region" aria-label="{{ __('Daftar pengajuan lembur; geser horizontal untuk melihat seluruh kolom') }}" tabindex="0">
                <table class="w-full min-w-[52rem] text-left text-sm">
                    <thead class="bg-sanmal-surface-secondary text-xs uppercase tracking-wide text-[var(--sanmal-text-muted)] dark:bg-sanmal-surface-secondary-dark">
                        <tr>
                            <th scope="col" class="px-4 py-3">{{ __('Pegawai') }}</th>
                            <th scope="col" class="px-4 py-3">{{ __('Jadwal mulai') }}</th>
                            <th scope="col" class="px-4 py-3">{{ __('Alasan') }}</th>
                            <th scope="col" class="px-4 py-3">{{ __('Persetujuan') }}</th>
                            <th scope="col" class="px-4 py-3">{{ __('Status operasional') }}</th>
                            <th scope="col" class="px-4 py-3 text-right">{{ __('Aksi') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-sanmal-border dark:divide-sanmal-border-dark">
                        @forelse ($requests as $request)
                            <tr class="transition-colors hover:bg-sanmal-surface-secondary/40 dark:hover:bg-sanmal-surface-elevated-dark/20">
                                <td class="px-4 py-3 font-medium text-[var(--sanmal-text-primary)]">{{ $request->employee->user->name }}</td>
                                <td class="whitespace-nowrap px-4 py-3 font-mono text-xs tabular-nums text-[var(--sanmal-text-secondary)]">{{ $request->scheduledStartLocal()->format('d M Y, H:i') }}</td>
                                <td class="max-w-sm px-4 py-3 text-[var(--sanmal-text-secondary)]">{{ Str::limit($request->reason ?? 'Alasan tidak dicantumkan', 110) }}</td>
                                <td class="whitespace-nowrap px-4 py-3"><x-ui.badge :color="$request->approval_status->badgeColor()">{{ $request->approval_status->label() }}</x-ui.badge></td>
                                <td class="whitespace-nowrap px-4 py-3"><x-ui.badge :color="$request->state->badgeColor()">{{ $request->state->label() }}</x-ui.badge></td>
                                <td class="whitespace-nowrap px-4 py-3 text-right">
                                    <x-ui.button href="{{ route('admin.overtime-requests.show', $request) }}" size="sm" variant="outline">{{ __('Tinjau') }}</x-ui.button>
                                </td>
                            </tr>
                        @empty
                            <x-ui.empty-state :colspan="6" :message="__('Tidak ada pengajuan lembur yang cocok.')" />
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-ui.card>
        {{ $requests->links() }}
    </x-ui.page>
</x-layouts::app>
