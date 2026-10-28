<x-layouts::app :title="'Permohonan Saya'">
    <div class="mx-auto max-w-5xl space-y-6 p-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <flux:heading size="xl" level="1">{{ __('Permohonan Izin, Sakit, dan Cuti') }}</flux:heading>
                <flux:text class="mt-1">{{ __('Pantau status permohonan dan keputusan admin.') }}</flux:text>
            </div>
            <flux:button href="{{ route('leave-requests.create') }}" variant="primary" icon="plus">
                {{ __('Buat permohonan') }}
            </flux:button>
        </div>

        @if (session('status'))
            <div class="rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-900 dark:border-green-800/60 dark:bg-green-950/40 dark:text-green-300">
                {{ session('status') }}
            </div>
        @endif

        <div class="overflow-x-auto rounded-xl border border-neutral-200 dark:border-neutral-700">
            <table class="w-full text-left text-sm">
                <thead class="bg-zinc-50 text-xs uppercase text-zinc-500 dark:bg-zinc-900 dark:text-zinc-400">
                    <tr>
                        <th class="px-4 py-3">{{ __('Jenis') }}</th>
                        <th class="px-4 py-3">{{ __('Tanggal') }}</th>
                        <th class="px-4 py-3">{{ __('Alasan') }}</th>
                        <th class="px-4 py-3">{{ __('Status') }}</th>
                        <th class="px-4 py-3">{{ __('Keputusan') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('Aksi') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-200 dark:divide-neutral-700">
                    @forelse ($requests as $item)
                        <tr class="align-top">
                            <td class="px-4 py-3 font-medium">{{ $item->leaveType?->name ?? 'Jenis dihapus' }}</td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <div>{{ $item->start_date->format('d/m/Y') }}</div>
                                <div class="text-xs text-zinc-500">&ndash; {{ $item->end_date->format('d/m/Y') }}</div>
                            </td>
                            <td class="min-w-48 px-4 py-3 text-zinc-700 dark:text-zinc-300">{{ $item->reason }}</td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <flux:badge :color="$item->status->badgeColor()">
                                    {{ $item->status->label() }}
                                </flux:badge>
                            </td>
                            <td class="min-w-40 px-4 py-3">
                                @if ($item->decidedBy)
                                    <div class="font-medium">{{ $item->decidedBy->name }}</div>
                                    <div class="text-xs text-zinc-500">{{ $item->decided_at?->format('d/m/Y H:i') }}</div>
                                @endif
                                @if ($item->decision_note)
                                    <p class="mt-1 text-xs text-zinc-600 dark:text-zinc-400">{{ $item->decision_note }}</p>
                                @endif
                                @if (! $item->decidedBy && ! $item->decision_note)
                                    <span class="text-zinc-400">&mdash;</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right">
                                @if ($item->isPending())
                                    <flux:modal.trigger name="cancel-leave-{{ $item->id }}">
                                        <flux:button size="sm" variant="danger">
                                            {{ __('Batalkan') }}
                                        </flux:button>
                                    </flux:modal.trigger>

                                    <flux:modal name="cancel-leave-{{ $item->id }}" class="max-w-md">
                                        <form method="POST" action="{{ route('leave-requests.cancel', $item) }}" class="space-y-4">
                                            @csrf
                                            @method('PATCH')
                                            <div>
                                                <flux:heading size="lg">{{ __('Konfirmasi Pembatalan') }}</flux:heading>
                                                <flux:text class="mt-2">
                                                    {{ __('Apakah Anda yakin ingin membatalkan permohonan :type ini?', ['type' => $item->leaveType?->name ?? '']) }}
                                                </flux:text>
                                            </div>

                                            <div class="flex justify-end gap-2 pt-2">
                                                <flux:modal.close>
                                                    <flux:button variant="filled">{{ __('Tutup') }}</flux:button>
                                                </flux:modal.close>
                                                <flux:button type="submit" variant="danger">{{ __('Ya, Batalkan') }}</flux:button>
                                            </div>
                                        </form>
                                    </flux:modal>
                                @else
                                    <span class="text-zinc-400">&mdash;</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-zinc-500">
                                {{ __('Belum ada permohonan.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $requests->links() }}
        </div>
    </div>
</x-layouts::app>
