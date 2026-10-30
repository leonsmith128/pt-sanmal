<x-layouts::app :title="'Detail Permohonan'">
    <div class="mx-auto max-w-3xl space-y-6 p-6">
        <div>
            <flux:button href="{{ route('admin.leave-requests.index') }}" variant="ghost" icon="arrow-left" size="sm">
                {{ __('Kembali ke daftar') }}
            </flux:button>
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

        @if ($errors->any())
            <div class="rounded-xl border border-red-300 bg-red-50 p-4 text-sm text-red-800 dark:border-red-800 dark:bg-red-950/40 dark:text-red-300">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="rounded-xl border border-neutral-200 bg-white p-6 dark:border-neutral-700 dark:bg-zinc-900">
            <dl class="grid gap-6 sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wider text-zinc-500">{{ __('Pegawai') }}</dt>
                    <dd class="mt-1 font-medium">{{ $leaveRequest->employee?->user?->name ?? 'Pegawai #'.$leaveRequest->employee_id }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wider text-zinc-500">{{ __('Jenis Permohonan') }}</dt>
                    <dd class="mt-1 font-medium">{{ $leaveRequest->leaveType?->name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wider text-zinc-500">{{ __('Periode Tanggal') }}</dt>
                    <dd class="mt-1">{{ $leaveRequest->start_date->format('d/m/Y') }} &ndash; {{ $leaveRequest->end_date->format('d/m/Y') }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wider text-zinc-500">{{ __('Waktu Pengajuan') }}</dt>
                    <dd class="mt-1">{{ $leaveRequest->created_at->format('d/m/Y H:i') }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-xs font-semibold uppercase tracking-wider text-zinc-500">{{ __('Alasan') }}</dt>
                    <dd class="mt-1 whitespace-pre-line text-zinc-800 dark:text-zinc-200">{{ $leaveRequest->reason }}</dd>
                </div>
                @if ($leaveRequest->attachment_path)
                    <div class="sm:col-span-2">
                        <dt class="text-xs font-semibold uppercase tracking-wider text-zinc-500">{{ __('Lampiran') }}</dt>
                        <dd class="mt-1">
                            <flux:button href="{{ route('admin.leave-requests.attachment', $leaveRequest) }}" size="sm" icon="arrow-down-tray" variant="filled">
                                {{ __('Unduh lampiran') }} ({{ basename($leaveRequest->attachment_path) }})
                            </flux:button>
                        </dd>
                    </div>
                @endif
                @if ($leaveRequest->decision_note)
                    <div class="sm:col-span-2">
                        <dt class="text-xs font-semibold uppercase tracking-wider text-zinc-500">{{ __('Catatan Keputusan') }}</dt>
                        <dd class="mt-1 text-zinc-800 dark:text-zinc-200">{{ $leaveRequest->decision_note }}</dd>
                    </div>
                @endif
            </dl>
        </div>

        @if ($leaveRequest->isPending())
            <form id="decision-form" method="POST" action="{{ route('admin.leave-requests.decide', $leaveRequest) }}" class="space-y-4 rounded-xl border border-neutral-200 bg-white p-6 dark:border-neutral-700 dark:bg-zinc-900">
                @csrf
                <flux:heading size="lg">{{ __('Keputusan Admin') }}</flux:heading>

                <flux:select name="decision" :label="__('Keputusan')" required>
                    <flux:select.option value="">{{ __('Pilih keputusan') }}</flux:select.option>
                    <flux:select.option value="approved">{{ __('Setujui') }}</flux:select.option>
                    <flux:select.option value="rejected">{{ __('Tolak') }}</flux:select.option>
                </flux:select>

                <flux:textarea name="decision_note" :label="__('Catatan (opsional)')" rows="3" maxlength="2000" placeholder="{{ __('Tuliskan catatan persetujuan atau penolakan...') }}">{{ old('decision_note') }}</flux:textarea>

                <flux:text class="text-xs text-zinc-500">
                    {{ __('Jika terdapat absensi aktual atau permohonan lain yang tumpang tindih, rekap harian akan menandainya sebagai perlu ditinjau.') }}
                </flux:text>

                <div class="flex justify-end pt-2">
                    <flux:modal.trigger name="confirm-decision">
                        <flux:button variant="primary">
                            {{ __('Simpan keputusan') }}
                        </flux:button>
                    </flux:modal.trigger>
                </div>

                <flux:modal name="confirm-decision" class="max-w-md">
                    <div class="space-y-4">
                        <flux:heading size="lg">{{ __('Konfirmasi Keputusan') }}</flux:heading>
                        <flux:text>
                            {{ __('Apakah Anda yakin ingin memproses dan menyimpan keputusan untuk permohonan ini?') }}
                        </flux:text>
                        <div class="flex justify-end gap-2 pt-2">
                            <flux:modal.close>
                                <flux:button variant="filled">{{ __('Batal') }}</flux:button>
                            </flux:modal.close>
                            <flux:button variant="primary" onclick="document.getElementById('decision-form').submit();">
                                {{ __('Ya, Simpan') }}
                            </flux:button>
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
