<x-layouts::app :title="__('Detail Permohonan')">
    <x-ui.page class="!max-w-3xl gap-5">
        <div>
            <x-ui.button href="{{ route('admin.leave-requests.index') }}" variant="outline" icon="arrow-left" size="sm">
                {{ __('Kembali ke daftar') }}
            </x-ui.button>
        </div>

        <x-ui.page-header
            :title="__('Detail Permohonan')"
            :description="__('Tinjau rincian pengajuan dan tentukan keputusan.')"
        >
            <x-ui.badge :color="$leaveRequest->status->badgeColor()" size="lg">
                {{ $leaveRequest->status->label() }}
            </x-ui.badge>
        </x-ui.page-header>

        <x-admin.flash-message />

        <x-ui.card padding="p-5 sm:p-6">
            <dl class="grid gap-6 sm:grid-cols-2">
                <x-ui.metadata-item
                    :label="__('Pegawai')"
                    label-class="text-xs font-semibold uppercase tracking-wider text-[var(--sanmal-text-muted)]"
                    value-class="mt-1 font-medium text-[var(--sanmal-text-primary)]"
                >
                    {{ $leaveRequest->employee?->user?->name ?? 'Pegawai #'.$leaveRequest->employee_id }}
                </x-ui.metadata-item>
                <x-ui.metadata-item
                    :label="__('Jenis Permohonan')"
                    label-class="text-xs font-semibold uppercase tracking-wider text-[var(--sanmal-text-muted)]"
                    value-class="mt-1 font-medium text-[var(--sanmal-text-primary)]"
                >
                    {{ $leaveRequest->leaveType?->name ?? '—' }}
                </x-ui.metadata-item>
                <x-ui.metadata-item
                    :label="__('Periode Tanggal')"
                    label-class="text-xs font-semibold uppercase tracking-wider text-[var(--sanmal-text-muted)]"
                    value-class="mt-1 font-medium text-[var(--sanmal-text-primary)]"
                >
                    {{ $leaveRequest->start_date->format('d/m/Y') }} &ndash; {{ $leaveRequest->end_date->format('d/m/Y') }}
                </x-ui.metadata-item>
                <x-ui.metadata-item
                    :label="__('Waktu Pengajuan')"
                    label-class="text-xs font-semibold uppercase tracking-wider text-[var(--sanmal-text-muted)]"
                    value-class="mt-1 font-mono text-xs text-[var(--sanmal-text-muted)]"
                >
                    {{ $leaveRequest->created_at->format('d/m/Y H:i') }}
                </x-ui.metadata-item>
                <x-ui.metadata-item
                    :label="__('Alasan')"
                    label-class="text-xs font-semibold uppercase tracking-wider text-[var(--sanmal-text-muted)]"
                    value-class="mt-1 whitespace-pre-line text-sm text-[var(--sanmal-text-secondary)]"
                    class="sm:col-span-2"
                >
                    {{ $leaveRequest->reason }}
                </x-ui.metadata-item>
                @if ($leaveRequest->attachment_path)
                    <x-ui.metadata-item
                        :label="__('Lampiran')"
                        label-class="text-xs font-semibold uppercase tracking-wider text-[var(--sanmal-text-muted)]"
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
                        label-class="text-xs font-semibold uppercase tracking-wider text-[var(--sanmal-text-muted)]"
                        value-class="mt-1 text-sm text-[var(--sanmal-text-secondary)] italic"
                        class="sm:col-span-2"
                    >
                        {{ $leaveRequest->decision_note }}
                    </x-ui.metadata-item>
                @endif
            </dl>
        </x-ui.card>

        @if ($leaveRequest->isPending())
            <x-ui.card padding="p-5 sm:p-6">
                <form id="decision-form" method="POST" action="{{ route('admin.leave-requests.decide', $leaveRequest) }}" class="space-y-4">
                    @csrf
                    <flux:heading size="lg">{{ __('Keputusan Admin') }}</flux:heading>

                    <x-ui.select name="decision" :label="__('Keputusan')" :value="old('decision')" required>
                        <flux:select.option value="">{{ __('Pilih keputusan') }}</flux:select.option>
                        <flux:select.option value="approved">{{ __('Setujui') }}</flux:select.option>
                        <flux:select.option value="rejected">{{ __('Tolak') }}</flux:select.option>
                    </x-ui.select>

                    <x-ui.textarea
                        name="decision_note"
                        :label="__('Catatan (opsional)')"
                        rows="3"
                        maxlength="2000"
                        :placeholder="__('Tuliskan catatan persetujuan atau penolakan...')"
                    >{{ old('decision_note') }}</x-ui.textarea>

                    <flux:text class="text-xs text-[var(--sanmal-text-muted)]">
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
            </x-ui.card>
        @else
            <x-ui.card padding="p-4" class="text-sm text-[var(--sanmal-text-secondary)]">
                {{ __('Permohonan sudah diproses oleh :name pada :date.', [
                    'name' => $leaveRequest->decidedBy?->name ?? 'admin',
                    'date' => $leaveRequest->decided_at?->format('d/m/Y H:i') ?? '—',
                ]) }}
            </x-ui.card>
        @endif
    </x-ui.page>
</x-layouts::app>
