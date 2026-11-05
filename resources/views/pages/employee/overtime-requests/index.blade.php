<x-layouts::app :title="__('Pengajuan Lembur')">
    <x-ui.page max-width="3xl" gap="6">
        <x-ui.page-header
            :title="__('Pengajuan lembur')"
            :description="__('Ajukan waktu mulai lembur sebelum pelaksanaan. Waktu selesai mengikuti clock-out aktual dan dicatat terpisah.')"
        >
            <x-ui.button href="{{ route('attendance') }}" variant="ghost" icon="arrow-left">{{ __('Absensi') }}</x-ui.button>
        </x-ui.page-header>

        <x-ui.flash-message />

        <x-ui.card padding="p-5">
            <flux:heading size="lg">{{ __('Ajukan lembur') }}</flux:heading>
            <flux:text class="mt-1 text-sm">{{ __('Pengajuan dapat dibuat sebelum clock-in, termasuk beberapa hari sebelumnya. Jadwal hanya memerlukan waktu mulai; selesai lembur dicatat saat clock-out.') }}</flux:text>
            <form method="POST" action="{{ route('overtime-requests.store') }}" class="mt-5 flex flex-col gap-4">
                @csrf
                <x-ui.input
                    name="scheduled_start_at"
                    :label="__('Jadwal mulai')"
                    type="datetime-local"
                    :value="old('scheduled_start_at')"
                    required
                />
                <x-ui.textarea
                    name="reason"
                    :label="__('Alasan lembur (opsional)')"
                    rows="3"
                    :placeholder="__('Jika diperlukan, jelaskan pekerjaan yang perlu diselesaikan...')"
                >{{ old('reason') }}</x-ui.textarea>
                @error('form') <flux:text class="text-sm text-red-600 dark:text-red-400">{{ $message }}</flux:text> @enderror
                <x-ui.button type="submit" variant="primary" class="w-full sm:w-auto">{{ __('Kirim pengajuan') }}</x-ui.button>
            </form>
        </x-ui.card>

        <x-ui.section id="riwayat-lembur" class="scroll-mt-6">
            <flux:heading size="lg">{{ __('Riwayat pengajuan lembur') }}</flux:heading>
            @forelse ($requests as $request)
                <x-ui.card padding="p-4">
                    <article>
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="font-medium text-[var(--sanmal-text-primary)]">{{ __('Mulai terjadwal:') }} {{ $request->scheduledStartLocal()->format('D, d M Y · H:i') }}</p>
                                @if ($request->reason)
                                    <p class="mt-1 whitespace-pre-line text-sm text-[var(--sanmal-text-secondary)]">{{ $request->reason }}</p>
                                @endif
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <x-ui.badge :color="$request->approval_status->badgeColor()">{{ $request->approval_status->label() }}</x-ui.badge>
                                <x-ui.badge :color="$request->state->badgeColor()">{{ $request->state->label() }}</x-ui.badge>
                            </div>
                        </div>
                        @if ($request->decision_note)
                            <div class="mt-3 rounded-lg border border-sanmal-border/40 bg-sanmal-surface-inset/60 p-3 text-sm text-[var(--sanmal-text-secondary)] dark:border-sanmal-border-dark/30 dark:bg-sanmal-surface-inset-dark/70">
                                <span class="font-medium text-[var(--sanmal-text-primary)]">{{ __('Catatan admin:') }}</span> {{ $request->decision_note }}
                            </div>
                        @endif
                        @if (in_array($request->state->value, ['scheduled', 'awaiting_photo'], true) && $request->start_record_id === null)
                            <form method="POST" action="{{ route('overtime-requests.cancel', $request) }}" class="mt-3" onsubmit="return confirm('Batalkan jadwal lembur ini? Setelah dibatalkan, Anda dapat clock-out jika sesi masih aktif.')">
                                @csrf @method('PATCH')
                                <x-ui.button type="submit" variant="ghost" size="sm" icon="x-circle" class="text-red-600 hover:bg-red-50 hover:text-red-700 dark:text-red-400 dark:hover:bg-red-950/30">{{ __('Batalkan Lembur') }}</x-ui.button>
                            </form>
                        @endif
                    </article>
                </x-ui.card>
            @empty
                <x-ui.card padding="p-8" class="text-center">
                    <flux:icon name="clock" class="mx-auto size-8 text-sanmal-brand-blue dark:text-sanmal-brand-blue-light" />
                    <flux:heading size="md" class="mt-3">{{ __('Belum ada pengajuan lembur') }}</flux:heading>
                    <flux:text class="mt-1">{{ __('Pengajuan yang Anda buat akan muncul di sini.') }}</flux:text>
                </x-ui.card>
            @endforelse
            <div>{{ $requests->links() }}</div>
        </x-ui.section>
    </x-ui.page>
</x-layouts::app>
