<x-layouts::app :title="'Persetujuan Permohonan'">
    <div class="mx-auto max-w-6xl space-y-6 p-6">
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
                <flux:select name="status" :label="__('Filter status')">
                    <flux:select.option value="">{{ __('Semua status') }}</flux:select.option>
                    @foreach (\App\Enums\LeaveRequestStatus::cases() as $case)
                        <flux:select.option value="{{ $case->value }}" :selected="request('status') === $case->value">
                            {{ $case->label() }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
            </div>
            <x-ui.button type="submit" variant="primary">{{ __('Terapkan') }}</x-ui.button>
            @if (request()->filled('status'))
                <x-ui.button href="{{ route('admin.leave-requests.index') }}" variant="outline">{{ __('Reset') }}</x-ui.button>
            @endif
        </form>
</x-admin.filter-panel>

        <div class="overflow-x-auto rounded-xl border border-neutral-200 dark:border-neutral-700">
            <table class="w-full text-left text-sm">
                <thead class="bg-zinc-50 text-xs uppercase text-zinc-500 dark:bg-zinc-900 dark:text-zinc-400">
                    <tr>
                        <th class="px-4 py-3">{{ __('Pegawai') }}</th>
                        <th class="px-4 py-3">{{ __('Jenis') }}</th>
                        <th class="px-4 py-3">{{ __('Periode') }}</th>
                        <th class="px-4 py-3">{{ __('Diajukan') }}</th>
                        <th class="px-4 py-3">{{ __('Status') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('Detail') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-200 dark:divide-neutral-700">
                    @forelse ($requests as $item)
                        <tr>
                            <td class="px-4 py-3 font-medium">
                                {{ $item->employee?->user?->name ?? 'Pegawai #'.$item->employee_id }}
                            </td>
                            <td class="px-4 py-3">{{ $item->leaveType?->name ?? '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3">
                                {{ $item->start_date->format('d/m/Y') }} &ndash; {{ $item->end_date->format('d/m/Y') }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-zinc-500">
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

        <div class="mt-4">
            {{ $requests->links() }}
        </div>
    </div>
</x-layouts::app>
