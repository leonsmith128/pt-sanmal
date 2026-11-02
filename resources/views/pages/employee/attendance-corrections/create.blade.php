<x-layouts::app :title="__('Ajukan Koreksi Absensi')">
    <div class="mx-auto w-full max-w-2xl space-y-5 px-4 py-4 sm:px-6 sm:py-6">
        <header class="space-y-2">
            <flux:button href="{{ route('leave-requests.index') }}" variant="ghost" icon="arrow-left" size="sm" class="w-fit">{{ __('Kembali') }}</flux:button>
            <flux:heading size="xl" level="1">{{ __('Ajukan Koreksi Absensi') }}</flux:heading>
            <flux:text>{{ __('Pilih event yang ingin dikoreksi. Untuk Clock Out yang terlupa, pilih sesi Clock In yang masih terbuka.') }}</flux:text>
        </header>

        @if ($errors->any())
            <flux:callout variant="danger" icon="exclamation-triangle">
                <flux:callout.heading>{{ __('Permohonan belum dapat dikirim') }}</flux:callout.heading>
                <flux:callout.text>{{ $errors->first() }}</flux:callout.text>
            </flux:callout>
        @endif

        <form method="POST" action="{{ route('attendance-corrections.store') }}" enctype="multipart/form-data"
            class="space-y-4 rounded-2xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900 sm:p-6">
            @csrf
            <flux:field>
                <flux:label>{{ __('Jenis event') }}</flux:label>
                <flux:select name="requested_type" required>
                    <flux:select.option value="clock_in" :selected="old('requested_type') === 'clock_in'">Clock In</flux:select.option>
                    <flux:select.option value="clock_out" :selected="old('requested_type', 'clock_out') === 'clock_out'">Clock Out</flux:select.option>
                </flux:select>
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Event absensi yang sudah tercatat (opsional)') }}</flux:label>
                <flux:select name="attendance_record_id">
                    <flux:select.option value="">{{ __('Kosongkan untuk Clock Out yang terlupa') }}</flux:select.option>
                    @foreach ($records as $record)
                        <flux:select.option value="{{ $record->id }}" :selected="(string) old('attendance_record_id') === (string) $record->id">
                            #{{ $record->id }} · {{ $record->type->value === 'clock_in' ? 'Clock In' : 'Clock Out' }} · {{ $record->recordedAtLocal()->format('d/m/Y H:i') }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="attendance_record_id" />
            </flux:field>

            @if ($openSession)
                <input type="hidden" name="session_anchor_id" value="{{ $openSession->id }}">
                <flux:callout variant="info" icon="info">
                    {{ __('Sesi terbuka: Clock In #') }}{{ $openSession->id }} · {{ $openSession->recordedAtLocal()->format('d/m/Y H:i') }}. {{ __('Untuk lupa Clock Out, kosongkan event di atas dan pilih Clock Out.') }}
                </flux:callout>
            @else
                <flux:text class="text-sm text-zinc-500">{{ __('Tidak ada sesi terbuka untuk pengajuan Clock Out yang terlupa.') }}</flux:text>
            @endif

            <flux:field>
                <flux:label>{{ __('Waktu yang benar (waktu lokal sesi)') }}</flux:label>
                <flux:input type="datetime-local" name="requested_recorded_at_local" value="{{ old('requested_recorded_at_local') }}" required />
                <flux:error name="requested_recorded_at_local" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Alasan') }}</flux:label>
                <flux:textarea name="reason" rows="4" required maxlength="2000">{{ old('reason') }}</flux:textarea>
                <flux:error name="reason" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Lampiran (opsional)') }}</flux:label>
                <flux:input type="file" name="attachment" accept=".jpg,.jpeg,.png,.pdf" />
                <flux:description>{{ __('JPG, PNG, atau PDF maksimal 1 MB.') }}</flux:description>
                <flux:error name="attachment" />
            </flux:field>

            <div class="flex justify-end gap-2 border-t border-zinc-200 pt-4 dark:border-zinc-700">
                <flux:button href="{{ route('leave-requests.index') }}" variant="ghost">{{ __('Batal') }}</flux:button>
                <flux:button type="submit" variant="primary">{{ __('Kirim koreksi') }}</flux:button>
            </div>
        </form>
    </div>
</x-layouts::app>
