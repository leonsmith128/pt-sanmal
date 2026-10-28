<x-layouts::app :title="'Buat Permohonan'">
    <div class="mx-auto max-w-2xl space-y-6 p-6">
        <div>
            <flux:button href="{{ route('leave-requests.index') }}" variant="ghost" icon="arrow-left" size="sm">
                {{ __('Kembali ke permohonan saya') }}
            </flux:button>
        </div>

        <div>
            <flux:heading size="xl" level="1">{{ __('Buat Permohonan') }}</flux:heading>
            <flux:text class="mt-1">{{ __('Permohonan akan berstatus menunggu sampai diputuskan oleh admin.') }}</flux:text>
        </div>

        @if ($errors->any())
            <div class="rounded-xl border border-red-300 bg-red-50 p-4 text-sm text-red-800 dark:border-red-800 dark:bg-red-950/40 dark:text-red-300">
                <p class="font-medium">{{ __('Periksa kembali formulir berikut:') }}</p>
                <ul class="mt-2 list-inside list-disc space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('leave-requests.store') }}" enctype="multipart/form-data" class="space-y-5 rounded-xl border border-neutral-200 bg-white p-6 dark:border-neutral-700 dark:bg-zinc-900">
            @csrf

            <flux:select name="leave_type_id" :label="__('Jenis permohonan')" required>
                <flux:select.option value="">{{ __('Pilih jenis permohonan') }}</flux:select.option>
                @foreach ($leaveTypes as $type)
                    <flux:select.option value="{{ $type->id }}" :selected="old('leave_type_id') == $type->id">
                        {{ $type->name }}
                    </flux:select.option>
                @endforeach
            </flux:select>
            @error('leave_type_id') <flux:text class="text-xs text-red-500">{{ $message }}</flux:text> @enderror

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <flux:input type="date" name="start_date" :label="__('Tanggal mulai')" value="{{ old('start_date') }}" required />
                    @error('start_date') <flux:text class="text-xs text-red-500">{{ $message }}</flux:text> @enderror
                </div>
                <div>
                    <flux:input type="date" name="end_date" :label="__('Tanggal selesai')" value="{{ old('end_date') }}" required />
                    @error('end_date') <flux:text class="text-xs text-red-500">{{ $message }}</flux:text> @enderror
                </div>
            </div>

            <div>
                <flux:textarea name="reason" :label="__('Alasan')" rows="4" required maxlength="5000" placeholder="{{ __('Tuliskan alasan permohonan...') }}">{{ old('reason') }}</flux:textarea>
                @error('reason') <flux:text class="text-xs text-red-500">{{ $message }}</flux:text> @enderror
            </div>

            <div>
                <flux:input type="file" name="attachment" :label="__('Lampiran (opsional)')" accept=".jpg,.jpeg,.png,.pdf" :description="__('JPG, PNG, atau PDF; maksimal 5 MB.')" />
                @error('attachment') <flux:text class="text-xs text-red-500">{{ $message }}</flux:text> @enderror
            </div>

            <div class="flex justify-end pt-2">
                <flux:button type="submit" variant="primary">
                    {{ __('Kirim permohonan') }}
                </flux:button>
            </div>
        </form>
    </div>
</x-layouts::app>
