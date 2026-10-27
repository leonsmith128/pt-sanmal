<x-layouts::app :title="'Detail Permohonan'">
    <div class="mx-auto max-w-3xl space-y-6 p-6">
        <a href="{{ route('admin.leave-requests.index') }}" class="text-sm underline">← Kembali ke daftar</a>
        <div>
            <h1 class="text-2xl font-semibold">Detail Permohonan</h1>
            <p class="mt-1 text-sm text-neutral-600">Status: {{ $leaveRequest->status }}</p>
        </div>
        @if (session('status'))<div class="rounded-lg border border-green-300 bg-green-50 p-3 text-sm text-green-900">{{ session('status') }}</div>@endif
        @if ($errors->any())<div class="rounded-lg border border-red-300 bg-red-50 p-3 text-sm text-red-800">{{ $errors->first() }}</div>@endif
        <dl class="grid gap-4 rounded-xl border p-5 sm:grid-cols-2">
            <div><dt class="text-sm text-neutral-500">Pegawai</dt><dd class="font-medium">{{ $leaveRequest->employee?->user?->name ?? 'Pegawai #'.$leaveRequest->employee_id }}</dd></div>
            <div><dt class="text-sm text-neutral-500">Jenis</dt><dd class="font-medium">{{ $leaveRequest->leaveType?->name ?? '—' }}</dd></div>
            <div><dt class="text-sm text-neutral-500">Periode</dt><dd>{{ $leaveRequest->start_date->format('d/m/Y') }} – {{ $leaveRequest->end_date->format('d/m/Y') }}</dd></div>
            <div><dt class="text-sm text-neutral-500">Dikirim</dt><dd>{{ $leaveRequest->created_at->format('d/m/Y H:i') }}</dd></div>
            <div class="sm:col-span-2"><dt class="text-sm text-neutral-500">Alasan</dt><dd class="whitespace-pre-line">{{ $leaveRequest->reason }}</dd></div>
            @if ($leaveRequest->attachment_path)<div class="sm:col-span-2"><dt class="text-sm text-neutral-500">Lampiran</dt><dd>File lampiran tersimpan secara privat: {{ basename($leaveRequest->attachment_path) }}</dd></div>@endif
            @if ($leaveRequest->decision_note)<div class="sm:col-span-2"><dt class="text-sm text-neutral-500">Catatan keputusan</dt><dd>{{ $leaveRequest->decision_note }}</dd></div>@endif
        </dl>

        @if ($leaveRequest->status === 'pending')
            <form method="POST" action="{{ route('admin.leave-requests.decide', $leaveRequest) }}" class="space-y-4 rounded-xl border p-5">
                @csrf
                <h2 class="text-lg font-semibold">Keputusan Admin</h2>
                <div><label for="decision" class="mb-1 block text-sm font-medium">Keputusan</label><select id="decision" name="decision" required class="w-full rounded-lg border p-2"><option value="">Pilih keputusan</option><option value="approved">Setujui</option><option value="rejected">Tolak</option></select></div>
                <div><label for="decision_note" class="mb-1 block text-sm font-medium">Catatan (opsional)</label><textarea id="decision_note" name="decision_note" rows="3" maxlength="2000" class="w-full rounded-lg border p-2">{{ old('decision_note') }}</textarea></div>
                <p class="text-sm text-neutral-500">Jika terdapat absensi aktual atau permohonan lain yang tumpang tindih, rekap harian akan menandainya sebagai perlu ditinjau.</p>
                <button type="submit" class="rounded-lg bg-black px-4 py-2 text-white" onclick="return confirm('Simpan keputusan untuk permohonan ini?')">Simpan keputusan</button>
            </form>
        @else
            <p class="rounded-lg border p-4 text-sm">Permohonan sudah diproses oleh {{ $leaveRequest->decidedBy?->name ?? 'admin' }} pada {{ $leaveRequest->decided_at?->format('d/m/Y H:i') ?? '—' }}.</p>
        @endif
    </div>
</x-layouts::app>
