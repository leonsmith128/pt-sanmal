<x-layouts::app :title="__('Buat Permohonan')">
    <x-ui.page max-width="2xl" gap="5" class="px-4 py-4 sm:px-6 sm:py-6">
        <x-ui.page-header
            :title="__('Buat Permohonan')"
            :description="__('Lengkapi informasi berikut. Permohonan akan menunggu keputusan admin.')"
        >
            <x-ui.button href="{{ route('leave-requests.index') }}" variant="ghost" icon="arrow-left" size="sm">
                {{ __('Kembali ke permohonan saya') }}
            </x-ui.button>
        </x-ui.page-header>

        @if ($errors->any())
            <flux:callout variant="danger" icon="exclamation-triangle">
                <flux:callout.heading>{{ __('Formulir belum dapat dikirim') }}</flux:callout.heading>
                <flux:callout.text>{{ __('Periksa kembali kolom yang ditandai dan coba kirim ulang.') }}</flux:callout.text>
            </flux:callout>
        @endif

        <form method="POST" action="{{ route('leave-requests.store') }}" enctype="multipart/form-data"
            class="space-y-5 rounded-2xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900 sm:p-6">
            @csrf

            <x-ui.select name="leave_type_id" :label="__('Jenis permohonan')" required>
                <flux:select.option value="">{{ __('Pilih jenis permohonan') }}</flux:select.option>
                @foreach ($leaveTypes as $type)
                    <flux:select.option value="{{ $type->id }}" :selected="(string) old('leave_type_id') === (string) $type->id">
                        {{ $type->name }}
                    </flux:select.option>
                @endforeach
            </x-ui.select>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-ui.input
                    name="start_date"
                    :label="__('Tanggal mulai')"
                    type="date"
                    :value="old('start_date')"
                    required
                />
                <x-ui.input
                    name="end_date"
                    :label="__('Tanggal selesai')"
                    type="date"
                    :value="old('end_date')"
                    required
                />
            </div>

            <x-ui.textarea
                name="reason"
                :label="__('Alasan permohonan')"
                :description="__('Maksimal 5.000 karakter.')"
                rows="4"
                required
                maxlength="5000"
                :placeholder="__('Tuliskan alasan permohonan secara jelas...')"
            >{{ old('reason') }}</x-ui.textarea>

            <x-ui.input
                name="attachment"
                :label="__('Lampiran (opsional)')"
                :description="__('Format JPG, PNG, atau PDF. Ukuran maksimal 1 MB.')"
                type="file"
                accept=".jpg,.jpeg,.png,.pdf"
            />

            <div class="flex flex-col-reverse gap-2 border-t border-zinc-200 pt-4 dark:border-zinc-700 sm:flex-row sm:justify-end">
                <x-ui.button href="{{ route('leave-requests.index') }}" variant="ghost" class="w-full sm:w-auto">
                    {{ __('Batal') }}
                </x-ui.button>
                <x-ui.button type="submit" variant="primary" icon="paper-airplane" class="w-full sm:w-auto">
                    {{ __('Kirim permohonan') }}
                </x-ui.button>
            </div>
        </form>
    </x-ui.page>
</x-layouts::app>
