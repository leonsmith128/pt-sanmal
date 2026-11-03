<x-layouts::app :title="__('Ajukan Koreksi Absensi')">
    <x-ui.page max-width="2xl" gap="5" class="px-4 py-4 sm:px-6 sm:py-6">
        <x-ui.page-header
            :title="__('Ajukan Koreksi Absensi')"
            :description="__('Pilih event yang ingin dikoreksi. Untuk Clock Out yang terlupa, pilih sesi Clock In yang masih terbuka.')"
        >
            <x-ui.button href="{{ route('leave-requests.index') }}" variant="ghost" icon="arrow-left" size="sm">{{ __('Kembali') }}</x-ui.button>
        </x-ui.page-header>

        @if ($errors->any())
            <flux:callout variant="danger" icon="exclamation-triangle">
                <flux:callout.heading>{{ __('Permohonan belum dapat dikirim') }}</flux:callout.heading>
                <flux:callout.text>{{ $errors->first() }}</flux:callout.text>
            </flux:callout>
        @endif

        <form method="POST" action="{{ route('attendance-corrections.store') }}" enctype="multipart/form-data"
            class="space-y-4 rounded-2xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900 sm:p-6">
            @csrf
            <x-ui.select name="requested_type" :label="__('Jenis event')" required>
                <flux:select.option value="clock_in" :selected="old('requested_type') === 'clock_in'">Clock In</flux:select.option>
                <flux:select.option value="clock_out" :selected="old('requested_type', 'clock_out') === 'clock_out'">Clock Out</flux:select.option>
            </x-ui.select>

            <x-ui.select
                name="attendance_record_id"
                :label="__('Event absensi yang sudah tercatat (opsional)')"
            >
                <flux:select.option value="">{{ __('Kosongkan untuk Clock Out yang terlupa') }}</flux:select.option>
                @foreach ($records as $record)
                    <flux:select.option value="{{ $record->id }}" :selected="(string) old('attendance_record_id') === (string) $record->id">
                        #{{ $record->id }} · {{ $record->type->value === 'clock_in' ? 'Clock In' : 'Clock Out' }} · {{ $record->recordedAtLocal()->format('d/m/Y H:i') }}
                    </flux:select.option>
                @endforeach
            </x-ui.select>

            @if ($openSession)
                <input type="hidden" name="session_anchor_id" value="{{ $openSession->id }}">
                <flux:callout variant="info" icon="info">
                    {{ __('Sesi terbuka: Clock In #') }}{{ $openSession->id }} · {{ $openSession->recordedAtLocal()->format('d/m/Y H:i') }}. {{ __('Untuk lupa Clock Out, kosongkan event di atas dan pilih Clock Out.') }}
                </flux:callout>
            @else
                <flux:text class="text-sm text-zinc-500">{{ __('Tidak ada sesi terbuka untuk pengajuan Clock Out yang terlupa.') }}</flux:text>
            @endif

            <x-ui.input
                name="requested_recorded_at_local"
                :label="__('Waktu yang benar (waktu lokal sesi)')"
                type="datetime-local"
                :value="old('requested_recorded_at_local')"
                required
            />

            <x-ui.textarea
                name="reason"
                :label="__('Alasan')"
                rows="4"
                required
                maxlength="2000"
            >{{ old('reason') }}</x-ui.textarea>

            <x-ui.input
                name="attachment"
                :label="__('Lampiran (opsional)')"
                :description="__('JPG, PNG, atau PDF maksimal 1 MB.')"
                type="file"
                accept=".jpg,.jpeg,.png,.pdf"
            />

            <div class="flex justify-end gap-2 border-t border-zinc-200 pt-4 dark:border-zinc-700">
                <x-ui.button href="{{ route('leave-requests.index') }}" variant="ghost">{{ __('Batal') }}</x-ui.button>
                <x-ui.button type="submit" variant="primary">{{ __('Kirim koreksi') }}</x-ui.button>
            </div>
        </form>
    </x-ui.page>
</x-layouts::app>
