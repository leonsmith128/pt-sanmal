<x-layouts::app :title="'Permohonan Saya'">
    <div class="mx-auto max-w-5xl space-y-6 p-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold">Permohonan Izin, Sakit, dan Cuti</h1>
                <p class="mt-1 text-sm text-neutral-600">Pantau status permohonan dan keputusan admin.</p>
            </div>
            <a href="{{ route('leave-requests.create') }}" class="rounded-lg bg-black px-4 py-2 text-sm font-medium text-white">Buat permohonan</a>
        </div>

        @if (session('status'))
            <div class="rounded-lg border border-green-300 bg-green-50 p-3 text-sm text-green-900">{{ session('status') }}</div>
        @endif

        <div class="overflow-x-auto rounded-xl border">
            <table class="w-full text-left text-sm">
                <thead class="bg-neutral-50">
                    <tr><th class="p-3">Jenis</th><th class="p-3">Tanggal</th><th class="p-3">Alasan</th><th class="p-3">Status</th><th class="p-3">Keputusan</th><th class="p-3">Aksi</th></tr>
                </thead>
                <tbody>
                    @forelse ($requests as $item)
                        <tr class="border-t align-top">
                            <td class="p-3">{{ $item->leaveType?->name ?? 'Jenis dihapus' }}</td>
                            <td class="p-3 whitespace-nowrap">{{ $item->start_date->format('d/m/Y') }}<br>— {{ $item->end_date->format('d/m/Y') }}</td>
                            <td class="p-3 min-w-48">{{ $item->reason }}</td>
                            <td class="p-3"><span class="rounded-full bg-neutral-100 px-2 py-1">{{ match($item->status) {'pending' => 'Menunggu', 'approved' => 'Disetujui', 'rejected' => 'Ditolak', 'cancelled' => 'Dibatalkan', default => $item->status} }}</span></td>
                            <td class="p-3 min-w-40">
                                @if ($item->decidedBy)
                                    {{ $item->decidedBy->name }}<br><span class="text-neutral-500">{{ $item->decided_at?->format('d/m/Y H:i') }}</span>
                                @endif
                                @if ($item->decision_note)
                                    <p class="mt-1">{{ $item->decision_note }}</p>
                                @endif
                            </td>
                            <td class="p-3">
                                @if ($item->status === 'pending')
                                    <form method="POST" action="{{ route('leave-requests.cancel', $item) }}" onsubmit="return confirm('Batalkan permohonan ini?')">
                                        @csrf @method('PATCH')
                                        <button class="text-red-700 underline" type="submit">Batalkan</button>
                                    </form>
                                @else
                                    <span class="text-neutral-400">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="p-8 text-center text-neutral-500">Belum ada permohonan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $requests->links() }}
    </div>
</x-layouts::app>
