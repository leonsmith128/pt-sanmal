<x-layouts::app :title="__('Detail Pengajuan Lembur')">
    <div class="mx-auto flex w-full max-w-3xl flex-col gap-6">
        <div class="flex items-center justify-between gap-3">
            <div>
                <flux:heading size="xl">{{ __('Detail pengajuan lembur') }}</flux:heading>
                <flux:text class="mt-1">{{ $overtimeRequest->employee->user->name }}</flux:text>
            </div>
            <flux:button href="{{ route('admin.overtime-requests.index') }}" variant="ghost" icon="arrow-left">{{ __('Kembali') }}</flux:button>
        </div>
        @if (session('status'))
            <flux:callout variant="success" icon="check-circle"><flux:callout.text>{{ session('status') }}</flux:callout.text></flux:callout>
        @endif
        <section class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex flex-wrap gap-2">
                <flux:badge :color="match($overtimeRequest->approval_status->value) {'approved' => 'green', 'rejected' => 'red', default => 'amber'}">{{ $overtimeRequest->approval_status->label() }}</flux:badge>
                <flux:badge>{{ $overtimeRequest->state->label() }}</flux:badge>
            </div>
            <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                <div><dt class="text-sm text-zinc-500">{{ __('Waktu mulai terjadwal') }}</dt><dd class="mt-1 font-medium">{{ $overtimeRequest->scheduledStartLocal()->format('d M Y, H:i T') }}</dd></div>
                <div><dt class="text-sm text-zinc-500">{{ __('Waktu foto konfirmasi') }}</dt><dd class="mt-1 font-medium">{{ $overtimeRequest->photo_confirmed_at?->setTimezone($overtimeRequest->timezone)->format('d M Y, H:i T') ?? '—' }}</dd></div>
                <div><dt class="text-sm text-zinc-500">{{ __('Clock-in terkait') }}</dt><dd class="mt-1 font-medium">{{ $overtimeRequest->clockInRecord->recordedAtLocal()->format('d M Y, H:i') }}</dd></div>
                <div><dt class="text-sm text-zinc-500">{{ __('Clock-out terkait') }}</dt><dd class="mt-1 font-medium">{{ $overtimeRequest->clockOutRecord?->recordedAtLocal()->format('d M Y, H:i') ?? '—' }}</dd></div>
            </dl>
            <div class="mt-4"><p class="text-sm text-zinc-500">{{ __('Alasan') }}</p><p class="mt-1 whitespace-pre-line">{{ $overtimeRequest->reason }}</p></div>
            @if ($overtimeRequest->decision_note)<div class="mt-4 rounded-lg bg-zinc-50 p-3 text-sm dark:bg-zinc-800"><strong>{{ __('Catatan keputusan') }}</strong><p>{{ $overtimeRequest->decision_note }}</p></div>@endif
            @if ($overtimeRequest->photo_exception_reason)<div class="mt-4 rounded-lg border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-200"><strong>{{ __('Pengecualian foto tercatat') }}</strong><p>{{ $overtimeRequest->photo_exception_reason }}</p></div>@endif
            @if ($overtimeRequest->startRecord?->selfie_path)
                <div class="mt-5">
                    <p class="mb-2 text-sm font-medium">{{ __('Foto mulai lembur') }}</p>
                    <a href="{{ route('admin.attendance.selfie', $overtimeRequest->startRecord) }}" target="_blank" rel="noopener" class="inline-flex">
                        <flux:button variant="filled" icon="image">{{ __('Lihat foto tersimpan') }}</flux:button>
                    </a>
                    <p class="mt-2 text-sm text-zinc-500">{{ $overtimeRequest->startRecord->address_snapshot ?? __('Alamat tidak tersedia') }}</p>
                </div>
            @endif
        </section>

        @if ($overtimeRequest->approval_status->value === 'pending')
            <section class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg">{{ __('Keputusan admin') }}</flux:heading>
                <form method="POST" action="{{ route('admin.overtime-requests.decide', $overtimeRequest) }}" class="mt-4 flex flex-col gap-4">
                    @csrf
                    <flux:field><flux:label>{{ __('Keputusan') }}</flux:label><flux:select name="decision" required><flux:select.option value="approved">{{ __('Setujui') }}</flux:select.option><flux:select.option value="rejected">{{ __('Tolak') }}</flux:select.option></flux:select></flux:field>
                    <flux:field><flux:label>{{ __('Catatan (opsional)') }}</flux:label><flux:textarea name="decision_note" rows="2">{{ old('decision_note') }}</flux:textarea><flux:error name="decision_note" /></flux:field>
                    <flux:button type="submit" variant="primary">{{ __('Simpan keputusan') }}</flux:button>
                </form>
            </section>
        @endif

        @if (in_array($overtimeRequest->state->value, ['scheduled', 'awaiting_photo'], true) && ! $overtimeRequest->start_record_id)
            <section class="rounded-2xl border border-amber-300 bg-amber-50 p-5 dark:border-amber-800 dark:bg-amber-950/30">
                <flux:heading size="lg">{{ __('Pengecualian foto mulai lembur') }}</flux:heading>
                <flux:text class="mt-1">{{ __('Gunakan hanya setelah verifikasi manual jika pegawai benar-benar lupa mengambil foto. Alasan akan masuk ke audit.') }}</flux:text>
                <form method="POST" action="{{ route('admin.overtime-requests.photo-exception', $overtimeRequest) }}" class="mt-4 flex flex-col gap-3">
                    @csrf
                    <flux:field><flux:label>{{ __('Alasan pengecualian (wajib)') }}</flux:label><flux:textarea name="photo_exception_reason" required minlength="5" rows="3">{{ old('photo_exception_reason') }}</flux:textarea><flux:error name="photo_exception_reason" /></flux:field>
                    <flux:button type="submit" variant="danger">{{ __('Catat pengecualian foto') }}</flux:button>
                </form>
            </section>
        @endif
    </div>
</x-layouts::app>
