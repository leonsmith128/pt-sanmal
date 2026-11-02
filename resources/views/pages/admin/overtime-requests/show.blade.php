<x-layouts::app :title="__('Detail Pengajuan Lembur')">
    <div class="mx-auto flex w-full max-w-3xl flex-col gap-6">
        <x-admin.page-header
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
        </x-admin.page-header>

        <x-admin.flash-message />

        <section class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex flex-wrap gap-2">
                <flux:badge
                    :color="$overtimeRequest->approval_status->badgeColor()"
                >
                    {{ $overtimeRequest->approval_status->label() }}
                </flux:badge>

                <flux:badge :color="$overtimeRequest->state->badgeColor()">{{ $overtimeRequest->state->label() }}</flux:badge>
            </div>

            <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-sm text-zinc-500">{{ __('Waktu mulai terjadwal') }}</dt>
                    <dd class="mt-1 font-medium">
                        {{ $overtimeRequest->scheduledStartLocal()->format('d M Y, H:i T') }}
                    </dd>
                </div>

                <div>
                    <dt class="text-sm text-zinc-500">{{ __('Waktu selesai terjadwal (data lama)') }}</dt>
                    <dd class="mt-1 font-medium">
                        {{ $overtimeRequest->scheduledEndLocal()?->format('d M Y, H:i T') ?? '—' }}
                    </dd>
                </div>

                <div>
                    <dt class="text-sm text-zinc-500">{{ __('Waktu aktual mulai') }}</dt>
                    <dd class="mt-1 font-medium">
                        {{ $overtimeRequest->startRecord?->effectiveRecordedAtLocal()->format('d M Y, H:i T') ?? '—' }}
                    </dd>
                </div>

                <div>
                    <dt class="text-sm text-zinc-500">{{ __('Waktu foto konfirmasi') }}</dt>
                    <dd class="mt-1 font-medium">
                        {{ $overtimeRequest->photo_confirmed_at?->setTimezone($overtimeRequest->timezone)->format('d M Y, H:i T') ?? '—' }}
                    </dd>
                </div>

                <div>
                    <dt class="text-sm text-zinc-500">{{ __('Clock-in terkait') }}</dt>
                    <dd class="mt-1 font-medium">
                        {{ $overtimeRequest->clockInRecord?->effectiveRecordedAtLocal()->format('d M Y, H:i') ?? '—' }}
                    </dd>
                </div>

                <div>
                    <dt class="text-sm text-zinc-500">{{ __('Clock-out terkait') }}</dt>
                    <dd class="mt-1 font-medium">
                        {{ $overtimeRequest->clockOutRecord?->effectiveRecordedAtLocal()->format('d M Y, H:i') ?? '—' }}
                    </dd>
                </div>
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
                        icon="image"
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
                    <div>
                        <dt class="text-sm text-zinc-500">{{ __('Sumber') }}</dt>
                        <dd class="font-medium">{{ ucfirst($overtimeRequest->finalization_source) }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-zinc-500">{{ __('Diputuskan pada') }}</dt>
                        <dd class="font-medium">{{ $overtimeRequest->finalized_at->setTimezone($overtimeRequest->timezone)->format('d M Y, H:i') }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-zinc-500">{{ __('Mulai final') }}</dt>
                        <dd class="font-medium">{{ $overtimeRequest->final_start_at?->setTimezone($overtimeRequest->timezone)->format('d M Y, H:i') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-zinc-500">{{ __('Selesai final') }}</dt>
                        <dd class="font-medium">{{ $overtimeRequest->final_end_at?->setTimezone($overtimeRequest->timezone)->format('d M Y, H:i') ?? '—' }}</dd>
                    </div>
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
                    <flux:field>
                        <flux:label>{{ __('Sumber waktu final') }}</flux:label>
                        <flux:select name="finalization_source" :value="old('finalization_source', $overtimeRequest->finalization_source)" required>
                            @if ($overtimeRequest->scheduled_end_at)
                                <flux:select.option value="scheduled">{{ __('Gunakan rentang jadwal lama') }}</flux:select.option>
                            @endif
                            <flux:select.option value="actual">{{ __('Gunakan aktual') }}</flux:select.option>
                            <flux:select.option value="manual">{{ __('Koreksi manual') }}</flux:select.option>
                        </flux:select>
                        <flux:error name="finalization_source" />
                    </flux:field>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <flux:field>
                            <flux:label>{{ __('Mulai manual') }}</flux:label>
                            <flux:input type="datetime-local" name="final_start_local" :value="old('final_start_local', $overtimeRequest->final_start_at?->setTimezone($overtimeRequest->timezone)->format('Y-m-d\\TH:i'))" />
                            <flux:error name="final_start_local" />
                        </flux:field>
                        <flux:field>
                            <flux:label>{{ __('Selesai manual') }}</flux:label>
                            <flux:input type="datetime-local" name="final_end_local" :value="old('final_end_local', $overtimeRequest->final_end_at?->setTimezone($overtimeRequest->timezone)->format('Y-m-d\\TH:i'))" />
                            <flux:error name="final_end_local" />
                        </flux:field>
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

                    <flux:field>
                        <flux:label>{{ __('Keputusan') }}</flux:label>
                        <flux:select name="decision" :value="old('decision')" required>
                            <flux:select.option value="">{{ __('Pilih keputusan') }}</flux:select.option>
                            <flux:select.option value="approved">{{ __('Setujui') }}</flux:select.option>
                            <flux:select.option value="rejected">{{ __('Tolak') }}</flux:select.option>
                        </flux:select>
                        <flux:error name="decision" />
                    </flux:field>

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
    </div>
</x-layouts::app>
