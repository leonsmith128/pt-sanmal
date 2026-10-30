<x-layouts::app :title="__('Pengajuan Lembur')">
    <div class="mx-auto flex w-full max-w-3xl flex-col gap-6">
        <div class="flex items-start justify-between gap-4">
            <div>
                <flux:heading size="xl">{{ __('Pengajuan lembur') }}</flux:heading>
                <flux:text class="mt-1">{{ __('Ajukan jadwal lembur sebelum pelaksanaan. Jadwal dan waktu aktual akan dicatat terpisah.') }}</flux:text>
            </div>
            <flux:button href="{{ route('attendance') }}" variant="ghost" icon="arrow-left">{{ __('Absensi') }}</flux:button>
        </div>

        @if (session('status'))
            <flux:callout variant="success" icon="check-circle"><flux:callout.text>{{ session('status') }}</flux:callout.text></flux:callout>
        @endif

        <section class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg">{{ __('Ajukan lembur') }}</flux:heading>
            <flux:text class="mt-1 text-sm">{{ __('Pengajuan dapat dibuat sebelum clock-in, termasuk beberapa hari sebelumnya. Untuk lembur lintas tengah malam, isi tanggal selesai pada hari berikutnya.') }}</flux:text>
            <form method="POST" action="{{ route('overtime-requests.store') }}" class="mt-5 flex flex-col gap-4">
                @csrf
                <flux:field>
                    <flux:label>{{ __('Jadwal mulai') }}</flux:label>
                    <flux:input type="datetime-local" name="scheduled_start_at" required value="{{ old('scheduled_start_at') }}" />
                    <flux:error name="scheduled_start_at" />
                </flux:field>
                <flux:field>
                    <flux:label>{{ __('Jadwal selesai') }}</flux:label>
                    <flux:input type="datetime-local" name="scheduled_end_at" required value="{{ old('scheduled_end_at') }}" />
                    <flux:error name="scheduled_end_at" />
                </flux:field>
                <flux:field>
                    <flux:label>{{ __('Alasan lembur') }}</flux:label>
                    <flux:textarea name="reason" rows="3" required placeholder="{{ __('Jelaskan pekerjaan yang perlu diselesaikan...') }}">{{ old('reason') }}</flux:textarea>
                    <flux:error name="reason" />
                </flux:field>
                @error('form') <flux:text class="text-sm text-red-600 dark:text-red-400">{{ $message }}</flux:text> @enderror
                <flux:button type="submit" variant="primary" class="w-full sm:w-auto">{{ __('Kirim pengajuan') }}</flux:button>
            </form>
        </section>

        <section id="riwayat-lembur" class="scroll-mt-6 flex flex-col gap-3">
            <flux:heading size="lg">{{ __('Riwayat pengajuan lembur') }}</flux:heading>
            @forelse ($requests as $request)
                <article class="rounded-2xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $request->scheduledStartLocal()->format('D, d M Y · H:i') }} — {{ $request->scheduledEndLocal()?->format('D, d M Y · H:i') ?? '—' }}</p>
                            <p class="mt-1 whitespace-pre-line text-sm text-zinc-600 dark:text-zinc-300">{{ $request->reason }}</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <flux:badge :color="match($request->approval_status->value) {'approved' => 'green', 'rejected' => 'red', default => 'amber'}">{{ $request->approval_status->label() }}</flux:badge>
                            <flux:badge :color="match($request->state->value) {'in_progress' => 'blue', 'completed' => 'green', 'needs_review' => 'red', 'cancelled' => 'zinc', default => 'amber'}">{{ $request->state->label() }}</flux:badge>
                        </div>
                    </div>
                    @if ($request->decision_note)
                        <p class="mt-3 rounded-lg bg-zinc-50 p-3 text-sm text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300"><strong>{{ __('Catatan admin:') }}</strong> {{ $request->decision_note }}</p>
                    @endif
                    @if (in_array($request->state->value, ['scheduled', 'awaiting_photo'], true) && $request->start_record_id === null)
                        <form method="POST" action="{{ route('overtime-requests.cancel', $request) }}" class="mt-3" onsubmit="return confirm('Batalkan jadwal lembur ini? Setelah dibatalkan, Anda dapat clock-out jika sesi masih aktif.')">
                            @csrf @method('PATCH')
                            <flux:button type="submit" variant="primary" color="red" size="sm" icon="x-circle">{{ __('Batalkan Lembur') }}</flux:button>
                        </form>
                    @endif
                </article>
            @empty
                <div class="rounded-2xl border border-dashed border-zinc-300 p-8 text-center dark:border-zinc-700">
                    <flux:icon name="clock" class="mx-auto size-8 text-zinc-400" />
                    <flux:heading size="md" class="mt-3">{{ __('Belum ada pengajuan lembur') }}</flux:heading>
                    <flux:text class="mt-1">{{ __('Pengajuan yang Anda buat akan muncul di sini.') }}</flux:text>
                </div>
            @endforelse
            <div>{{ $requests->links() }}</div>
        </section>
    </div>
</x-layouts::app>
