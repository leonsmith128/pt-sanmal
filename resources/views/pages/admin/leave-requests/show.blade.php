<x-layouts::app :title="__('Detail Permohonan')">
    <div class="mx-auto w-full max-w-3xl space-y-5">
        <div>
            <x-ui.button href="{{ route('admin.leave-requests.index') }}" variant="outline" icon="arrow-left" size="sm">
                {{ __('Kembali ke daftar') }}
            </x-ui.button>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <flux:heading size="xl" level="1">{{ __('Detail Permohonan') }}</flux:heading>
                <flux:text class="mt-1">{{ __('Tinjau rincian pengajuan dan tentukan keputusan.') }}</flux:text>
            </div>
            <flux:badge size="lg" :color="$leaveRequest->status->badgeColor()">
                {{ $leaveRequest->status->label() }}
            </flux:badge>
        </div>

        <x-admin.flash-message />

        <div class="rounded-xl border border-neutral-200 bg-white p-6 dark:border-neutral-700 dark:bg-zinc-900">
            <dl class="grid gap-6 sm:grid-cols-2">
                <x-ui.metadata-item
                    :label="__('Pegawai')"
                    label-class="text-xs font-semibold uppercase tracking-wider text-zinc-500"
                    value-class="mt-1 font-medium"
                >
                    {{ $leaveRequest->employee?->user?->name ?? 'Pegawai #'.$leaveRequest->employee_id }}
                </x-ui.metadata-item>
                <x-ui.metadata-item
                    :label="__('Jenis Permohonan')"
                    label-class="text-xs font-semibold uppercase tracking-wider text-zinc-500"
                    value-class="mt-1 font-medium"
                >
                    {{ $leaveRequest->leaveType?->name ?? '—' }}
                </x-ui.metadata-item>
                <x-ui.metadata-item
                    :label="__('Periode Tanggal')"
                    label-class="text-xs font-semibold uppercase tracking-wider text-zinc-500"
                    value-class="mt-1"
                >
                    {{ $leaveRequest->start_date->format('d/m/Y') }} &ndash; {{ $leaveRequest->end_date->format('d/m/Y') }}
                </x-ui.metadata-item>
                <x-ui.metadata-item
                    :label="__('Waktu Pengajuan')"
                    label-class="text-xs font-semibold uppercase tracking-wider text-zinc-500"
                    value-class="mt-1"
                >
                    {{ $leaveRequest->created_at->format('d/m/Y H:i') }}
                </x-ui.metadata-item>
                <x-ui.metadata-item
                    :label="__('Alasan')"
                    label-class="text-xs font-semibold uppercase tracking-wider text-zinc-500"
                    value-class="mt-1 whitespace-pre-line text-zinc-800 dark:text-zinc-200"
                    class="sm:col-span-2"
                >
                    {{ $leaveRequest->reason }}
                </x-ui.metadata-item>
                @if ($leaveRequest->attachment_path)
                    <x-ui.metadata-item
                        :label="__('Lampiran')"
                        label-class="text-xs font-semibold uppercase tracking-wider text-zinc-500"
                        value-class="mt-1"
                        class="sm:col-span-2"
                    >
                        <x-ui.button href="{{ route('admin.leave-requests.attachment', $leaveRequest) }}" size="sm" icon="arrow-down-tray" variant="outline">
                            {{ __('Unduh lampiran') }} ({{ basename($leaveRequest->attachment_path) }})
                        </x-ui.button>
                    </x-ui.metadata-item>
                @endif
                @if ($leaveRequest->decision_note)
                    <x-ui.metadata-item
                        :label="__('Catatan Keputusan')"
                        label-class="text-xs font-semibold uppercase tracking-wider text-zinc-500"
                        value-class="mt-1 text-zinc-800 dark:text-zinc-200"
                        class="sm:col-span-2"
                    >
                        {{ $leaveRequest->decision_note }}
                    </x-ui.metadata-item>
                @endif
            </dl>
        </div>

        @if ($leaveRequest->isPending())
            <form id="decision-form" method="POST" action="{{ route('admin.leave-requests.decide', $leaveRequest) }}" class="space-y-4 rounded-xl border border-neutral-200 bg-white p-6 dark:border-neutral-700 dark:bg-zinc-900">
                @csrf
                <flux:heading size="lg">{{ __('Keputusan Admin') }}</flux:heading>

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
                    rows="3"
                    maxlength="2000"
                    :placeholder="__('Tuliskan catatan persetujuan atau penolakan...')"
                >{{ old('decision_note') }}</x-ui.textarea>

                <flux:text class="text-xs text-zinc-500">
                    {{ __('Jika terdapat absensi aktual atau permohonan lain yang tumpang tindih, rekap harian akan menandainya sebagai perlu ditinjau.') }}
                </flux:text>

                <div class="flex justify-end pt-2">
                    <flux:modal.trigger name="confirm-decision">
                        <x-ui.button variant="primary">{{ __('Simpan keputusan') }}</x-ui.button>
                    </flux:modal.trigger>
                </div>

                <flux:modal name="confirm-decision" class="max-w-md">
                    <div class="space-y-4">
                        <flux:heading size="lg">{{ __('Konfirmasi Keputusan') }}</flux:heading>
                        <flux:text>{{ __('Apakah Anda yakin ingin memproses dan menyimpan keputusan untuk permohonan ini?') }}</flux:text>
                        <div class="flex justify-end gap-2 pt-2">
                            <flux:modal.close>
                                <x-ui.button variant="outline">{{ __('Batal') }}</x-ui.button>
                            </flux:modal.close>
                            <x-ui.button variant="primary" onclick="document.getElementById('decision-form').submit();">
                                {{ __('Ya, Simpan') }}
                            </x-ui.button>
                        </div>
                    </div>
                </flux:modal>
            </form>
        @else
            <div class="rounded-xl border border-neutral-200 bg-zinc-50 p-4 text-sm text-zinc-700 dark:border-neutral-700 dark:bg-zinc-800/40 dark:text-zinc-300">
                {{ __('Permohonan sudah diproses oleh :name pada :date.', [
                    'name' => $leaveRequest->decidedBy?->name ?? 'admin',
                    'date' => $leaveRequest->decided_at?->format('d/m/Y H:i') ?? '—',
                ]) }}
            </div>
        @endif
    </div>
</x-layouts::app>
