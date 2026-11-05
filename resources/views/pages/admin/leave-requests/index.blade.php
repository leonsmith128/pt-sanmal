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

        <x-admin.filter-panel>
            <form method="GET" action="{{ route('admin.leave-requests.index') }}" class="flex flex-wrap items-end gap-3">
                <div class="w-full sm:w-64">
                    <x-ui.select name="status" :label="__('Filter status')">
                        <flux:select.option value="">{{ __('Semua status') }}</flux:select.option>
                        @foreach (\App\Enums\LeaveRequestStatus::cases() as $case)
                            <flux:select.option value="{{ $case->value }}" :selected="request('status') === $case->value">
                                {{ $case->label() }}
                            </flux:select.option>
                        @endforeach
                    </x-ui.select>
                </div>
                <x-ui.button type="submit" variant="primary">{{ __('Terapkan') }}</x-ui.button>
                @if (request()->filled('status'))
                    <x-ui.button href="{{ route('admin.leave-requests.index') }}" variant="outline">{{ __('Reset') }}</x-ui.button>
                @endif
            </form>
        </x-admin.filter-panel>

        <x-ui.card padding="p-0" class="overflow-hidden">
            <div class="overflow-x-auto">
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
