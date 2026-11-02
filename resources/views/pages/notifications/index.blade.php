<x-layouts::app :title="__('Notifikasi')">
    <div class="mx-auto flex w-full max-w-3xl flex-col gap-5 px-4 py-4 sm:px-6 sm:py-6">
        <header class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <flux:heading size="xl">{{ __('Notifikasi sistem') }}</flux:heading>
                <flux:text class="mt-1">{{ __('Pemberitahuan aktivitas dan perubahan yang tercatat oleh sistem.') }}</flux:text>
            </div>
            @if (auth()->user()->unreadNotifications()->exists())
                <form method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf
                    @method('PATCH')
                    <flux:button type="submit" variant="ghost" icon="check-check">{{ __('Tandai semua dibaca') }}</flux:button>
                </form>
            @endif
        </header>

        @if (session('status'))
            <flux:callout variant="success" icon="check-circle">
                <flux:callout.text>{{ session('status') }}</flux:callout.text>
            </flux:callout>
        @endif

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
                            <flux:icon name="{{ $isResolved ? 'calendar-x' : 'bell' }}" class="size-4" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <h2 class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $data['title'] ?? __('Notifikasi sistem') }}</h2>
                                @if (! $notification->read_at)
                                    <flux:badge color="sky">{{ __('Baru') }}</flux:badge>
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
                                @if (is_string($url) && str_starts_with($url, url('/')))
                                    <form method="POST" action="{{ route('notifications.read', $notification) }}">
                                        @csrf
                                        @method('PATCH')
                                        <flux:button type="submit" size="sm" variant="ghost" icon="arrow-up-right">
                                            {{ $isResolved ? __('Lihat riwayat lembur') : __('Buka') }}
                                        </flux:button>
                                    </form>
                                @endif
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
    </div>
</x-layouts::app>
