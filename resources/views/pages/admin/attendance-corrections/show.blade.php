<x-layouts::app :title="__('Detail Koreksi Absensi')">
    <div class="mx-auto max-w-3xl space-y-5">
        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="xl">{{ __('Detail Koreksi Absensi') }}</flux:heading>
                <flux:text>{{ $correction->employee?->user?->name ?? 'Pegawai #'.$correction->employee_id }}</flux:text>
            </div>
            <flux:button href="{{ route('admin.attendance-corrections.index') }}" variant="ghost">{{ __('Kembali') }}</flux:button>
        </div>

        <x-admin.flash-message />

        <section class="space-y-3 rounded-xl border border-zinc-200 p-5 dark:border-zinc-700">
            <dl class="grid gap-3 sm:grid-cols-2">
                <div>
                    <dt class="text-sm text-zinc-500">{{ __('Tanggal') }}</dt>
                    <dd>{{ $correction->attendance_date->format('d M Y') }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-zinc-500">{{ __('Jenis') }}</dt>
                    <dd>{{ $correction->requested_type }}</dd>
                </div>
                @if ($correction->attendanceRecord)
                    <div>
                        <dt class="text-sm text-zinc-500">{{ __('Waktu bukti asli (immutable)') }}</dt>
                        <dd>{{ $correction->attendanceRecord->recordedAtLocal()->format('d M Y H:i T') }}</dd>
                    </div>
                @endif
                <div>
                    <dt class="text-sm text-zinc-500">{{ __('Waktu yang diminta') }}</dt>
                    <dd>{{ $correction->requested_recorded_at->setTimezone($correction->attendanceRecord?->timezone ?? config('app.timezone'))->format('d M Y H:i T') }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-zinc-500">{{ __('Status') }}</dt>
                    <dd>{{ ucfirst($correction->status) }}</dd>
                </div>
            </dl>

            @if ($correction->status === 'approved' && $correction->attendanceRecord)
                <flux:callout variant="success" icon="check-circle">
                    <flux:callout.text>{{ __('Waktu efektif laporan menggunakan waktu yang disetujui. Timestamp bukti asli tetap tersimpan tanpa perubahan.') }}</flux:callout.text>
                </flux:callout>
            @endif

            <div>
                <p class="text-sm text-zinc-500">{{ __('Alasan pegawai') }}</p>
                <p class="whitespace-pre-line">{{ $correction->reason }}</p>
            </div>

            @if ($correction->review_note)
                <div>
                    <p class="text-sm text-zinc-500">{{ __('Catatan peninjau') }}</p>
                    <p>{{ $correction->review_note }}</p>
                </div>
            @endif

            @if ($correction->attachment_path)
                <flux:button href="{{ route('admin.attendance-corrections.attachment', $correction) }}" icon="paperclip">
                    {{ __('Unduh lampiran') }}
                </flux:button>
            @endif
        </section>

        @if ($correction->status === 'pending')
            <form method="POST" action="{{ route('admin.attendance-corrections.decide', $correction) }}" class="space-y-3 rounded-xl border border-zinc-200 p-5 dark:border-zinc-700">
                @csrf
                <flux:field>
                    <flux:label>{{ __('Keputusan') }}</flux:label>
                    <flux:select name="decision" :value="old('decision')" required>
                        <flux:select.option value="approved">{{ __('Setujui') }}</flux:select.option>
                        <flux:select.option value="rejected">{{ __('Tolak') }}</flux:select.option>
                    </flux:select>
                    <flux:error name="decision" />
                </flux:field>
                <flux:field>
                    <flux:label>{{ __('Catatan (opsional)') }}</flux:label>
                    <flux:textarea name="review_note">{{ old('review_note') }}</flux:textarea>
                    <flux:error name="review_note" />
                </flux:field>
                <flux:button type="submit" variant="primary">{{ __('Simpan keputusan') }}</flux:button>
            </form>
        @endif
    </div>
</x-layouts::app>
