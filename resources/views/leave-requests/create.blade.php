<x-layouts::app :title="'Buat Permohonan'">
    <div class="mx-auto max-w-2xl space-y-6 p-6">
        <a href="{{ route('leave-requests.index') }}" class="text-sm underline">← Kembali ke permohonan saya</a>
        <div>
            <h1 class="text-2xl font-semibold">Buat Permohonan</h1>
            <p class="mt-1 text-sm text-neutral-600">Permohonan akan berstatus menunggu sampai diputuskan admin.</p>
        </div>

        @if ($errors->any())
            <div class="rounded-lg border border-red-300 bg-red-50 p-3 text-sm text-red-800">
                <p class="font-semibold">Periksa kembali data berikut:</p>
                <ul class="mt-2 list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="POST" action="{{ route('leave-requests.store') }}" enctype="multipart/form-data" class="space-y-5 rounded-xl border p-5">
            @csrf
            <div>
                <label for="leave_type_id" class="mb-1 block text-sm font-medium">Jenis permohonan</label>
                <select id="leave_type_id" name="leave_type_id" required class="w-full rounded-lg border p-2">
                    <option value="">Pilih jenis</option>
                    @foreach ($leaveTypes as $type)
                        <option value="{{ $type->id }}" @selected(old('leave_type_id') == $type->id)>{{ $type->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div><label for="start_date" class="mb-1 block text-sm font-medium">Tanggal mulai</label><input id="start_date" name="start_date" type="date" required value="{{ old('start_date') }}" class="w-full rounded-lg border p-2"></div>
                <div><label for="end_date" class="mb-1 block text-sm font-medium">Tanggal selesai</label><input id="end_date" name="end_date" type="date" required value="{{ old('end_date') }}" class="w-full rounded-lg border p-2"></div>
            </div>
            <div><label for="reason" class="mb-1 block text-sm font-medium">Alasan</label><textarea id="reason" name="reason" rows="4" required maxlength="5000" class="w-full rounded-lg border p-2">{{ old('reason') }}</textarea></div>
            <div><label for="attachment" class="mb-1 block text-sm font-medium">Lampiran (opsional)</label><input id="attachment" name="attachment" type="file" accept=".jpg,.jpeg,.png,.pdf" class="w-full rounded-lg border p-2"><p class="mt-1 text-xs text-neutral-500">JPG, PNG, atau PDF; maksimal 5 MB.</p></div>
            <button type="submit" class="rounded-lg bg-black px-4 py-2 text-white">Kirim permohonan</button>
        </form>
    </div>
</x-layouts::app>
