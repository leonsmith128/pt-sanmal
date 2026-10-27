<x-layouts::app :title="'Rekap Absensi Harian'">
    <div class="mx-auto max-w-7xl space-y-6 p-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div><h1 class="text-2xl font-semibold">Rekap Absensi Harian</h1><p class="mt-1 text-sm text-neutral-600">Status cuti, sakit, dan izin dihitung dari permohonan yang disetujui. Tidak ada event absensi palsu yang dibuat.</p></div>
            <a href="{{ route('admin.leave-requests.index') }}" class="rounded-lg border px-4 py-2 text-sm">Daftar permohonan</a>
        </div>
        @if (session('status'))<div class="rounded-lg border border-green-300 bg-green-50 p-3 text-sm text-green-900">{{ session('status') }}</div>@endif
        @if ($errors->any())<div class="rounded-lg border border-red-300 bg-red-50 p-3 text-sm text-red-800">{{ $errors->first() }}</div>@endif
        <div class="flex flex-wrap items-end gap-3">
            <form method="GET" action="{{ route('admin.daily-attendance') }}" class="flex items-end gap-2"><div><label for="date" class="mb-1 block text-sm">Tanggal rekap</label><input id="date" name="date" type="date" value="{{ $date }}" required class="rounded-lg border p-2"></div><button class="rounded-lg border px-4 py-2">Lihat</button></form>
            <form method="GET" action="{{ route('admin.daily-attendance.export') }}" class="flex items-end gap-2"><div><label for="month" class="mb-1 block text-sm">Ekspor bulan</label><input id="month" name="month" type="month" value="{{ \Illuminate\Support\Carbon::parse($date)->format('Y-m') }}" required class="rounded-lg border p-2"></div><button class="rounded-lg bg-black px-4 py-2 text-white">Unduh CSV</button></form>
        </div>
        <div class="space-y-4">
            @forelse ($rows as $row)
                <section class="rounded-xl border p-4">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div><h2 class="font-semibold">{{ $row['employee']->user?->name ?? 'Pegawai #'.$row['employee']->id }}</h2><p class="text-sm text-neutral-500">ID Pegawai: {{ $row['employee']->employee_number ?? $row['employee']->id }}</p></div>
                        <span class="rounded-full border px-3 py-1 text-sm">{{ $row['daily']['label'] }}</span>
                    </div>
                    @if ($row['daily']['events']->isNotEmpty())
                        <div class="mt-3 flex flex-wrap gap-2 text-sm">
                            @foreach ($row['daily']['events'] as $event)
                                <span class="rounded bg-neutral-100 px-2 py-1">{{ $event->type->label() }}: {{ $event->recordedAtLocal()->format('H:i') }}</span>
                            @endforeach
                        </div>
                    @endif
                    @if ($row['daily']['reason'])
                        <p class="mt-2 text-sm text-amber-800">{{ $row['daily']['reason'] }}</p>
                    @endif
                    @if ($row['daily']['needs_review'])
                        <p class="mt-2 text-sm font-medium text-red-700">Perlu koreksi eksplisit oleh admin untuk menyelesaikan konflik.</p>
                    @endif
                    <details class="mt-3">
                        <summary class="cursor-pointer text-sm font-medium underline">Koreksi status harian</summary>
                        <form method="POST" action="{{ route('admin.daily-attendance.correct') }}" class="mt-3 grid gap-3 sm:grid-cols-2">
                            @csrf
                            <input type="hidden" name="employee_id" value="{{ $row['employee']->id }}">
                            <input type="hidden" name="attendance_date" value="{{ $date }}">
                            <div><label class="mb-1 block text-sm" for="status-{{ $row['employee']->id }}">Status final</label><select id="status-{{ $row['employee']->id }}" name="status" required class="w-full rounded-lg border p-2"><option value="present" @selected($row['daily']['status'] === 'present')>Hadir</option><option value="permission" @selected($row['daily']['status'] === 'permission')>Izin</option><option value="sick" @selected($row['daily']['status'] === 'sick')>Sakit</option><option value="leave" @selected($row['daily']['status'] === 'leave')>Cuti</option><option value="absent" @selected($row['daily']['status'] === 'absent')>Tidak hadir</option></select></div>
                            <div><label class="mb-1 block text-sm" for="reason-{{ $row['employee']->id }}">Alasan wajib</label><input id="reason-{{ $row['employee']->id }}" name="reason" required minlength="5" maxlength="2000" value="{{ $row['daily']['correction']?->reason }}" class="w-full rounded-lg border p-2" placeholder="Jelaskan alasan koreksi"></div>
                            <div class="sm:col-span-2"><button type="submit" class="rounded-lg border px-3 py-2 text-sm" onclick="return confirm('Simpan koreksi status? Koreksi akan tercatat di audit log.')">Simpan koreksi</button></div>
                        </form>
                        @if ($row['daily']['correction'])
                            <p class="mt-2 text-xs text-neutral-500">Koreksi terakhir dibuat oleh {{ $row['daily']['correction']->createdBy?->name ?? 'admin' }}.</p>
                        @endif
                    </details>
                </section>
            @empty
                <p class="rounded-xl border p-8 text-center text-neutral-500">Belum ada data pegawai.</p>
            @endforelse
        </div>
    </div>
</x-layouts::app>
