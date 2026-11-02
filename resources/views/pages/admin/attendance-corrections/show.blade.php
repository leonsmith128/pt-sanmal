<x-layouts::app :title="__('Detail Koreksi Absensi')">
    <div class="mx-auto w-full max-w-3xl space-y-5">
        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="xl">{{ __('Detail Koreksi Absensi') }}</flux:heading>
                <flux:text>{{ $correction->employee?->user?->name ?? 'Pegawai #'.$correction->employee_id }}</flux:text>
            </div>
            <x-ui.button href="{{ route('admin.attendance-corrections.index') }}" variant="outline">{{ __('Kembali') }}</x-ui.button>
        </div>

        <x-admin.flash-message />

        <section class="space-y-3 rounded-xl border border-zinc-200 p-5 dark:border-zinc-700">
            <dl class="grid gap-3 sm:grid-cols-2">
                <x-ui.metadata-item :label="__('Tanggal')">
                    {{ $correction->attendance_date->format('d M Y') }}
                </x-ui.metadata-item>
                <x-ui.metadata-item :label="__('Jenis')">
                    {{ $correction->requested_type }}
                </x-ui.metadata-item>
                @if ($correction->sessionAnchor)
                    <x-ui.metadata-item :label="__('Anchor sesi Clock In')">
                        #{{ $correction->sessionAnchor->id }} · {{ $correction->sessionAnchor->recordedAtLocal()->format('d M Y H:i T') }}
                    </x-ui.metadata-item>
                @endif
                @if ($correction->attendanceRecord)
                    <x-ui.metadata-item :label="__('Waktu bukti asli (immutable)')">
                        {{ $correction->attendanceRecord->recordedAtLocal()->format('d M Y H:i T') }}
                    </x-ui.metadata-item>
                @endif
                <x-ui.metadata-item :label="__('Waktu yang diminta')">
                    {{ $correction->requested_recorded_at->setTimezone($correction->sessionAnchor?->timezone ?? $correction->attendanceRecord?->timezone ?? config('app.timezone'))->format('d M Y H:i T') }}
                </x-ui.metadata-item>
                <x-ui.metadata-item :label="__('Status')">
                    {{ ucfirst($correction->status) }}
                </x-ui.metadata-item>
            </dl>

            @php($snapshotRecord = $correction->sessionAnchor ?? $correction->attendanceRecord)
            @if ($snapshotRecord)
                <div class="rounded-lg bg-zinc-50 p-3 dark:bg-zinc-800">
                    <p class="mb-2 text-sm font-medium">{{ __('Identitas pekerjaan saat Clock In') }}</p>
                    <dl class="grid gap-2 text-sm sm:grid-cols-2">
                        @if ($snapshotRecord->client_name_snapshot)
                            <x-ui.metadata-item :label="__('Client')" label-class="text-zinc-500" value-class="text-zinc-900 dark:text-zinc-100">
                                {{ $snapshotRecord->client_code_snapshot ? $snapshotRecord->client_code_snapshot.' · ' : '' }}{{ $snapshotRecord->client_name_snapshot }}
                            </x-ui.metadata-item>
                        @endif
                        @if ($snapshotRecord->project_name_snapshot)
                            <x-ui.metadata-item :label="__('Proyek')" label-class="text-zinc-500" value-class="text-zinc-900 dark:text-zinc-100">
                                {{ $snapshotRecord->project_code_snapshot ? $snapshotRecord->project_code_snapshot.' · ' : '' }}{{ $snapshotRecord->project_name_snapshot }}
                            </x-ui.metadata-item>
                        @endif
                        @if ($snapshotRecord->location_name_snapshot)
                            <x-ui.metadata-item :label="__('Lokasi')" label-class="text-zinc-500" value-class="text-zinc-900 dark:text-zinc-100">
                                {{ $snapshotRecord->location_name_snapshot }}
                            </x-ui.metadata-item>
                        @endif
                        <x-ui.metadata-item :label="__('Sumber snapshot')" label-class="text-zinc-500" value-class="text-zinc-900 dark:text-zinc-100">
                            {{ $snapshotRecord->snapshot_source === 'migration_backfill' ? __('Backfill migrasi (baseline data master)') : ($snapshotRecord->snapshot_source === 'clock_in' ? __('Diambil saat Clock In') : '—') }}
                        </x-ui.metadata-item>
                    </dl>
                </div>
            @endif

            @if ($correction->status === 'approved' && $correction->attendanceRecord)
                <div>
                    <p class="text-sm text-zinc-500">{{ __('Waktu efektif saat ini') }}</p>
                    <p>{{ $correction->attendanceRecord->effectiveRecordedAtLocal()->format('d M Y H:i T') }}</p>
                    @if ($correction->attendanceRecord->approvedCorrection?->is($correction))
                        <flux:callout variant="success" icon="check-circle" class="mt-2">
                            <flux:callout.text>{{ __('Permohonan ini menjadi koreksi terakhir yang menentukan waktu efektif. Timestamp bukti asli tetap tersimpan tanpa perubahan.') }}</flux:callout.text>
                        </flux:callout>
                    @else
                        <flux:callout variant="warning" icon="information-circle" class="mt-2">
                            <flux:callout.text>{{ __('Permohonan ini pernah disetujui, tetapi sudah ada koreksi yang lebih baru. Waktu efektif saat ini mengikuti koreksi terakhir.') }}</flux:callout.text>
                        </flux:callout>
                    @endif
                </div>
            @endif
            @if ($correction->generatedAttendanceRecord)
                <flux:callout variant="success" icon="check-circle">
                    <flux:callout.text>
                        {{ __('Event Clock Out baru #') }}{{ $correction->generatedAttendanceRecord->id }}{{ __(' dibuat dan ditautkan ke sesi asli.') }}
                        {{ __('Waktu efektif event:') }} {{ $correction->generatedAttendanceRecord->effectiveRecordedAtLocal()->format('d M Y H:i T') }}
                    </flux:callout.text>
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
                <x-ui.button href="{{ route('admin.attendance-corrections.attachment', $correction) }}" variant="outline" icon="paperclip">
                    {{ __('Unduh lampiran') }}
                </x-ui.button>
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
                <x-ui.button type="submit" variant="primary">{{ __('Simpan keputusan') }}</x-ui.button>
            </form>
        @endif
    </div>
</x-layouts::app>
