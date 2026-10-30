<x-layouts::app :title="__('Buat Permohonan')">
    <div class="mx-auto w-full max-w-2xl space-y-5 px-4 py-4 sm:px-6 sm:py-6">
        <header class="space-y-3">
            <flux:button href="{{ route('leave-requests.index') }}" variant="ghost" icon="arrow-left" size="sm" class="w-fit">
                {{ __('Kembali ke permohonan saya') }}
            </flux:button>
            <div>
                <flux:heading size="xl" level="1">{{ __('Buat Permohonan') }}</flux:heading>
                <flux:text class="mt-1">{{ __('Lengkapi informasi berikut. Permohonan akan menunggu keputusan admin.') }}</flux:text>
            </div>
        </header>

        @if ($errors->any())
            <flux:callout variant="danger" icon="exclamation-triangle">
                <flux:callout.heading>{{ __('Formulir belum dapat dikirim') }}</flux:callout.heading>
                <flux:callout.text>{{ __('Periksa kembali kolom yang ditandai dan coba kirim ulang.') }}</flux:callout.text>
            </flux:callout>
        @endif

        <form method="POST" action="{{ route('leave-requests.store') }}" enctype="multipart/form-data"
            class="space-y-5 rounded-2xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900 sm:p-6">
            @csrf

            <flux:field>
                <flux:label>{{ __('Jenis permohonan') }}</flux:label>
                <flux:select name="leave_type_id" required>
                    <flux:select.option value="">{{ __('Pilih jenis permohonan') }}</flux:select.option>
                    @foreach ($leaveTypes as $type)
                        <flux:select.option value="{{ $type->id }}" :selected="(string) old('leave_type_id') === (string) $type->id">
                            {{ $type->name }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="leave_type_id" />
            </flux:field>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <flux:field>
                    <flux:label>{{ __('Tanggal mulai') }}</flux:label>
                    <flux:input type="date" name="start_date" value="{{ old('start_date') }}" required />
                    <flux:error name="start_date" />
                </flux:field>
                <flux:field>
                    <flux:label>{{ __('Tanggal selesai') }}</flux:label>
                    <flux:input type="date" name="end_date" value="{{ old('end_date') }}" required />
                    <flux:error name="end_date" />
                </flux:field>
            </div>

            <flux:field>
                <flux:label>{{ __('Alasan permohonan') }}</flux:label>
                <flux:textarea name="reason" rows="4" required maxlength="5000"
                    placeholder="{{ __('Tuliskan alasan permohonan secara jelas...') }}">{{ old('reason') }}</flux:textarea>
                <flux:description>{{ __('Maksimal 5.000 karakter.') }}</flux:description>
                <flux:error name="reason" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Lampiran (opsional)') }}</flux:label>
                <flux:input type="file" name="attachment" accept=".jpg,.jpeg,.png,.pdf" />
                <flux:description>{{ __('Format JPG, PNG, atau PDF. Ukuran maksimal 5 MB.') }}</flux:description>
                <flux:error name="attachment" />
            </flux:field>

            <div class="flex flex-col-reverse gap-2 border-t border-zinc-200 pt-4 dark:border-zinc-700 sm:flex-row sm:justify-end">
                <flux:button href="{{ route('leave-requests.index') }}" variant="ghost" class="w-full sm:w-auto">
                    {{ __('Batal') }}
                </flux:button>
                <flux:button type="submit" variant="primary" icon="paper-airplane" class="w-full sm:w-auto">
                    {{ __('Kirim permohonan') }}
                </flux:button>
            </div>
        </form>
    </div>
</x-layouts::app>
