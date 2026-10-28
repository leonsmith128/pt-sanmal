<x-layouts::app :title="__('Pengelolaan Lembur')">
    <div class="mx-auto flex w-full max-w-6xl flex-col gap-6">
        <div>
            <flux:heading size="xl">{{ __('Pengelolaan lembur') }}</flux:heading>
            <flux:text class="mt-1">{{ __('Tinjau pengajuan, status konfirmasi foto, dan keputusan lembur pegawai.') }}</flux:text>
        </div>
        @if (session('status'))
            <flux:callout variant="success" icon="check-circle"><flux:callout.text>{{ session('status') }}</flux:callout.text></flux:callout>
        @endif
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <flux:field class="min-w-48">
                <flux:label>{{ __('Status persetujuan') }}</flux:label>
                <flux:select name="status" :value="request('status')">
                    <flux:select.option value="">{{ __('Semua status') }}</flux:select.option>
                    <flux:select.option value="pending">{{ __('Menunggu') }}</flux:select.option>
                    <flux:select.option value="approved">{{ __('Disetujui') }}</flux:select.option>
                    <flux:select.option value="rejected">{{ __('Ditolak') }}</flux:select.option>
                </flux:select>
            </flux:field>
            <flux:button type="submit" variant="primary">{{ __('Filter') }}</flux:button>
        </form>
        <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
            <div class="divide-y divide-zinc-200 dark:divide-zinc-800">
                @forelse ($requests as $request)
                    <a href="{{ route('admin.overtime-requests.show', $request) }}" class="flex flex-col gap-3 p-4 transition hover:bg-zinc-50 sm:flex-row sm:items-center sm:justify-between dark:hover:bg-zinc-800/60">
                        <div>
                            <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $request->employee->user->name }}</p>
                            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ $request->scheduledStartLocal()->format('d M Y, H:i') }} · {{ Str::limit($request->reason, 110) }}</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <flux:badge :color="match($request->approval_status->value) {'approved' => 'green', 'rejected' => 'red', default => 'amber'}">{{ $request->approval_status->label() }}</flux:badge>
                            <flux:badge>{{ $request->state->label() }}</flux:badge>
                            <flux:icon name="chevron-right" class="size-4 text-zinc-400" />
                        </div>
                    </a>
                @empty
                    <div class="p-10 text-center"><flux:text>{{ __('Tidak ada pengajuan lembur yang cocok.') }}</flux:text></div>
                @endforelse
            </div>
        </div>
        {{ $requests->links() }}
    </div>
</x-layouts::app>
