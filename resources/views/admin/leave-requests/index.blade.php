<x-layouts::app :title="'Persetujuan Permohonan'">
    <div class="mx-auto max-w-6xl space-y-6 p-6">
        <div>
            <h1 class="text-2xl font-semibold">Persetujuan Permohonan</h1>
            <p class="mt-1 text-sm text-neutral-600">Admin dan superadmin menggunakan alur persetujuan yang sama.</p>
        </div>
        @if (session('status'))<div class="rounded-lg border border-green-300 bg-green-50 p-3 text-sm text-green-900">{{ session('status') }}</div>@endif
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div><label for="status" class="mb-1 block text-sm">Filter status</label><select id="status" name="status" class="rounded-lg border p-2"><option value="">Semua</option>@foreach (['pending'=>'Menunggu','approved'=>'Disetujui','rejected'=>'Ditolak','cancelled'=>'Dibatalkan'] as $value=>$label)<option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>@endforeach</select></div>
            <button class="rounded-lg border px-4 py-2">Terapkan</button>
            <a href="{{ route('admin.daily-attendance') }}" class="rounded-lg border px-4 py-2">Rekap harian</a>
        </form>
        <div class="overflow-x-auto rounded-xl border">
            <table class="w-full text-left text-sm">
                <thead class="bg-neutral-50"><tr><th class="p-3">Pegawai</th><th class="p-3">Jenis</th><th class="p-3">Periode</th><th class="p-3">Diajukan</th><th class="p-3">Status</th><th class="p-3">Detail</th></tr></thead>
                <tbody>
                    @forelse ($requests as $item)
                        <tr class="border-t"><td class="p-3">{{ $item->employee?->user?->name ?? 'Pegawai #'.$item->employee_id }}</td><td class="p-3">{{ $item->leaveType?->name ?? '—' }}</td><td class="p-3 whitespace-nowrap">{{ $item->start_date->format('d/m/Y') }} – {{ $item->end_date->format('d/m/Y') }}</td><td class="p-3">{{ $item->created_at->format('d/m/Y H:i') }}</td><td class="p-3">{{ match($item->status) {'pending'=>'Menunggu','approved'=>'Disetujui','rejected'=>'Ditolak','cancelled'=>'Dibatalkan',default=>$item->status} }}</td><td class="p-3"><a class="underline" href="{{ route('admin.leave-requests.show', $item) }}">Buka</a></td></tr>
                    @empty
                        <tr><td colspan="6" class="p-8 text-center text-neutral-500">Tidak ada permohonan pada filter ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $requests->links() }}
    </div>
</x-layouts::app>
