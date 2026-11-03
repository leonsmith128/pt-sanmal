<x-layouts::app :title="__('Detail Pengajuan Lembur')">
    <x-ui.page class="!max-w-3xl gap-6">
        <x-ui.page-header
            :title="__('Detail pengajuan lembur')"
            :description="$overtimeRequest->employee->user->name"
        >
            <x-ui.button
                href="{{ route('admin.overtime-requests.index') }}"
                variant="outline"
                icon="arrow-left"
            >
                {{ __('Kembali') }}
            </x-ui.button>
        </x-ui.page-header>

        <x-admin.flash-message />

        <section class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex flex-wrap gap-2">
                <x-ui.badge :color="$overtimeRequest->approval_status->badgeColor()">
                    {{ $overtimeRequest->approval_status->label() }}
                </x-ui.badge>

                <x-ui.badge :color="$overtimeRequest->state->badgeColor()">
                    {{ $overtimeRequest->state->label() }}
                </x-ui.badge>
            </div>

            <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                <x-ui.metadata-item :label="__('Waktu mulai terjadwal')" value-class="mt-1 font-medium">
                    {{ $overtimeRequest->scheduledStartLocal()->format('d M Y, H:i T') }}
                </x-ui.metadata-item>

                <x-ui.metadata-item :label="__('Waktu selesai terjadwal (data lama)')" value-class="mt-1 font-medium">
                    {{ $overtimeRequest->scheduledEndLocal()?->format('d M Y, H:i T') ?? '—' }}
                </x-ui.metadata-item>

                <x-ui.metadata-item :label="__('Waktu aktual mulai')" value-class="mt-1 font-medium">
                    {{ $overtimeRequest->startRecord?->effectiveRecordedAtLocal()->format('d M Y, H:i T') ?? '—' }}
                </x-ui.metadata-item>

                <x-ui.metadata-item :label="__('Waktu foto konfirmasi')" value-class="mt-1 font-medium">
                    {{ $overtimeRequest->photo_confirmed_at?->setTimezone($overtimeRequest->timezone)->format('d M Y, H:i T') ?? '—' }}
                </x-ui.metadata-item>

                <x-ui.metadata-item :label="__('Clock-in terkait')" value-class="mt-1 font-medium">
                    {{ $overtimeRequest->clockInRecord?->effectiveRecordedAtLocal()->format('d M Y, H:i') ?? '—' }}
                </x-ui.metadata-item>

                <x-ui.metadata-item :label="__('Clock-out terkait')" value-class="mt-1 font-medium">
                    {{ $overtimeRequest->clockOutRecord?->effectiveRecordedAtLocal()->format('d M Y, H:i') ?? '—' }}
                </x-ui.metadata-item>
            </dl>

            <div class="mt-4">
                <p class="text-sm text-zinc-500">{{ __('Alasan') }}</p>
                <p class="mt-1 whitespace-pre-line">{{ $overtimeRequest->reason }}</p>
            </div>

            @if ($overtimeRequest->decision_note)
                <div class="mt-4 rounded-lg bg-zinc-50 p-3 text-sm dark:bg-zinc-800">
                    <strong>{{ __('Catatan keputusan') }}</strong>
                    <p class="mt-1 whitespace-pre-line">{{ $overtimeRequest->decision_note }}</p>
                </div>
            @endif

            @if ($overtimeRequest->photo_exception_reason)
                <div class="mt-4 rounded-lg border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-200">
                    <strong>{{ __('Pengecualian foto tercatat') }}</strong>
                    <p class="mt-1 whitespace-pre-line">{{ $overtimeRequest->photo_exception_reason }}</p>
                </div>
            @endif

            @if ($overtimeRequest->startRecord?->selfie_path)
                <div class="mt-5">
                    <p class="mb-2 text-sm font-medium">{{ __('Foto mulai lembur') }}</p>

                    <x-ui.button
                        href="{{ route('admin.attendance.selfie', $overtimeRequest->startRecord) }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        variant="outline"
                        icon="photo"
                    >
                        {{ __('Lihat foto tersimpan') }}
                    </x-ui.button>

                    <p class="mt-2 text-sm text-zinc-500">
                        {{ $overtimeRequest->startRecord->address_snapshot ?? __('Alamat tidak tersedia') }}
                    </p>
                </div>
            @endif
        </section>

        @if ($overtimeRequest->finalized_at)
            <section class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 dark:border-emerald-900 dark:bg-emerald-950/30">
                <flux:heading size="lg">{{ __('Rentang waktu final') }}</flux:heading>
                <dl class="mt-3 grid gap-3 sm:grid-cols-2">
                    <x-ui.metadata-item :label="__('Sumber')" value-class="font-medium">
                        {{ ucfirst($overtimeRequest->finalization_source) }}
                    </x-ui.metadata-item>
                    <x-ui.metadata-item :label="__('Diputuskan pada')" value-class="font-medium">
                        {{ $overtimeRequest->finalized_at->setTimezone($overtimeRequest->timezone)->format('d M Y, H:i') }}
                    </x-ui.metadata-item>
                    <x-ui.metadata-item :label="__('Mulai final')" value-class="font-medium">
                        {{ $overtimeRequest->final_start_at?->setTimezone($overtimeRequest->timezone)->format('d M Y, H:i') ?? '—' }}
                    </x-ui.metadata-item>
                    <x-ui.metadata-item :label="__('Selesai final')" value-class="font-medium">
                        {{ $overtimeRequest->final_end_at?->setTimezone($overtimeRequest->timezone)->format('d M Y, H:i') ?? '—' }}
                    </x-ui.metadata-item>
                </dl>
                <p class="mt-3 whitespace-pre-line text-sm">{{ $overtimeRequest->finalization_reason }}</p>
            </section>
        @endif

        @if ($overtimeRequest->clock_out_record_id)
            <section class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg">{{ __('Finalisasi rentang lembur') }}</flux:heading>
                <flux:text class="mt-1">{{ $overtimeRequest->scheduled_end_at ? __('Pilih rentang jadwal lama, aktual, atau koreksi manual. Data event asli tidak akan diubah. Alasan wajib dicatat.') : __('Jadwal baru hanya menyimpan waktu mulai. Pilih waktu aktual atau koreksi manual untuk menetapkan rentang final. Data event asli tidak akan diubah.') }}</flux:text>

                <form method="POST" action="{{ route('admin.overtime-requests.finalize', $overtimeRequest) }}" class="mt-4 flex flex-col gap-4">
                    @csrf
                    <x-ui.select
                        name="finalization_source"
                        :label="__('Sumber waktu final')"
                        :value="old('finalization_source', $overtimeRequest->finalization_source)"
                        required
                    >
                        @if ($overtimeRequest->scheduled_end_at)
                            <flux:select.option value="scheduled">{{ __('Gunakan rentang jadwal lama') }}</flux:select.option>
                        @endif
                        <flux:select.option value="actual">{{ __('Gunakan aktual') }}</flux:select.option>
                        <flux:select.option value="manual">{{ __('Koreksi manual') }}</flux:select.option>
                    </x-ui.select>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-ui.input
                            name="final_start_local"
                            type="datetime-local"
                            :label="__('Mulai manual')"
                            :value="old('final_start_local', $overtimeRequest->final_start_at?->setTimezone($overtimeRequest->timezone)->format('Y-m-d\\TH:i'))"
                        />
                        <x-ui.input
                            name="final_end_local"
                            type="datetime-local"
                            :label="__('Selesai manual')"
                            :value="old('final_end_local', $overtimeRequest->final_end_at?->setTimezone($overtimeRequest->timezone)->format('Y-m-d\\TH:i'))"
                        />
                    </div>

                    <x-ui.textarea
                        name="finalization_reason"
                        :label="__('Alasan finalisasi (wajib)')"
                        rows="3"
                        required
                    >{{ old('finalization_reason', $overtimeRequest->finalization_reason) }}</x-ui.textarea>

                    <div>
                        <x-ui.button type="submit" variant="primary">{{ __('Simpan rentang final') }}</x-ui.button>
                    </div>
                </form>
            </section>
        @endif

        @if ($overtimeRequest->approval_status->value === 'pending')
            <section class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg">{{ __('Keputusan admin') }}</flux:heading>

                <form
                    method="POST"
                    action="{{ route('admin.overtime-requests.decide', $overtimeRequest) }}"
                    class="mt-4 flex flex-col gap-4"
                >
                    @csrf

                    <x-ui.select name="decision" :label="__('Keputusan')" :value="old('decision')" required>
                        <flux:select.option value="">{{ __('Pilih keputusan') }}</flux:select.option>
                        <flux:select.option value="approved">{{ __('Setujui') }}</flux:select.option>
                        <flux:select.option value="rejected">{{ __('Tolak') }}</flux:select.option>
                    </x-ui.select>

                    <x-ui.textarea
                        name="decision_note"
                        :label="__('Catatan (opsional)')"
                        rows="2"
                    >{{ old('decision_note') }}</x-ui.textarea>

                    <div>
                        <x-ui.button type="submit" variant="primary">
                            {{ __('Simpan keputusan') }}
                        </x-ui.button>
                    </div>
                </form>
            </section>
        @endif

        @if (in_array($overtimeRequest->state->value, ['scheduled', 'awaiting_photo'], true) && ! $overtimeRequest->start_record_id)
            <section class="rounded-2xl border border-amber-300 bg-amber-50 p-5 dark:border-amber-800 dark:bg-amber-950/30">
                <flux:heading size="lg">{{ __('Pengecualian foto mulai lembur') }}</flux:heading>
                <flux:text class="mt-1">
                    {{ __('Gunakan hanya setelah verifikasi manual jika pegawai benar-benar lupa mengambil foto. Alasan akan masuk ke audit.') }}
                </flux:text>

                <form
                    method="POST"
                    action="{{ route('admin.overtime-requests.photo-exception', $overtimeRequest) }}"
                    class="mt-4 flex flex-col gap-3"
                >
                    @csrf

                    <x-ui.textarea
                        name="photo_exception_reason"
                        :label="__('Alasan pengecualian (wajib)')"
                        required
                        minlength="5"
                        rows="3"
                    >{{ old('photo_exception_reason') }}</x-ui.textarea>

                    <div>
                        <x-ui.button type="submit" variant="danger">
                            {{ __('Catat pengecualian foto') }}
                        </x-ui.button>
                    </div>
                </form>
            </section>
        @endif
    </x-ui.page>
</x-layouts::app>
