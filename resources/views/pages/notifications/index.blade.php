<x-layouts::app :title="__('Notifikasi')">
    <x-ui.page max-width="3xl" gap="5" class="px-4 py-4 sm:px-6 sm:py-6">
        <x-ui.page-header
            :title="__('Notifikasi sistem')"
            :description="__('Pemberitahuan aktivitas dan perubahan yang tercatat oleh sistem.')"
        >
            @if (auth()->user()->unreadNotifications()->exists())
                <form method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf
                    @method('PATCH')
                    <x-ui.button type="submit" variant="outline" icon="check">{{ __('Tandai semua dibaca') }}</x-ui.button>
                </form>
            @endif
        </x-ui.page-header>

        <x-ui.flash-message />

        <section class="flex flex-col gap-3">
            @forelse ($notifications as $notification)
                @php
                    $data = $notification->data;
                    $isResolved = ($data['event_status'] ?? null) === 'cancelled';
                    $url = $data['url'] ?? route('dashboard');
                @endphp
                <article @class([
                    'rounded-xl border p-4 dark:border-zinc-700',
                    'border-zinc-200 bg-white dark:bg-zinc-900' => $notification->read_at,
                    'border-sky-200 bg-sky-50/60 dark:border-sky-900 dark:bg-sky-950/20' => ! $notification->read_at,
                ])>
                    <div class="flex items-start gap-3">
                        <div class="mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-full {{ $notification->read_at ? 'bg-zinc-100 text-zinc-500 dark:bg-zinc-800' : 'bg-sky-100 text-sky-700 dark:bg-sky-900 dark:text-sky-300' }}">
                            <flux:icon name="{{ $isResolved ? 'x-circle' : 'bell' }}" class="size-4" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <h2 class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $data['title'] ?? __('Notifikasi sistem') }}</h2>
                                @if (! $notification->read_at)
                                    <x-ui.badge color="sky">{{ __('Baru') }}</x-ui.badge>
                                @endif
                            </div>
                            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ $data['message'] ?? __('Ada pembaruan pada akun Anda.') }}</p>
                            @if ($isResolved)
                                <p class="mt-2 text-xs font-medium text-zinc-500">{{ __('Tidak memerlukan tindakan — pengajuan terkait telah dibatalkan.') }}</p>
                            @endif
                            <div class="mt-3 flex flex-wrap items-center justify-between gap-2">
                                <time class="text-xs text-zinc-500" datetime="{{ $notification->created_at->toISOString() }}" title="{{ $notification->created_at->format('d M Y H:i:s') }}">
                                    {{ __('Masuk') }} {{ $notification->created_at->timezone(config('app.timezone'))->translatedFormat('d M Y, H:i') }}
                                </time>
                                @if (! empty($data['resolved_at']))
                                    <time class="text-xs text-zinc-500" datetime="{{ $data['resolved_at'] }}" title="{{ $data['resolved_at'] }}">
                                        {{ __('Diperbarui') }} {{ \Illuminate\Support\Carbon::parse($data['resolved_at'])->timezone(config('app.timezone'))->translatedFormat('d M Y, H:i') }}
                                    </time>
                                @endif
                                <form method="POST" action="{{ route('notifications.read', $notification) }}">
                                    @csrf
                                    @method('PATCH')
                                    <x-ui.button type="submit" size="sm" variant="outline" icon="arrow-up-right">
                                        {{ $isResolved ? __('Lihat riwayat lembur') : __('Buka') }}
                                    </x-ui.button>
                                </form>
                            </div>
                        </div>
                    </div>
                </article>
            @empty
                <div class="rounded-xl border border-dashed border-zinc-300 p-8 text-center dark:border-zinc-700">
                    <flux:icon name="bell" class="mx-auto size-8 text-zinc-400" />
                    <flux:heading size="md" class="mt-3">{{ __('Belum ada notifikasi') }}</flux:heading>
                    <flux:text class="mt-1">{{ __('Pemberitahuan sistem akan muncul di halaman ini.') }}</flux:text>
                </div>
            @endforelse
            {{ $notifications->links() }}
        </section>
    </x-ui.page>
</x-layouts::app>
