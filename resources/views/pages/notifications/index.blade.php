<x-layouts::app :title="__('Notifikasi')">
    <x-ui.page max-width="3xl" gap="5">
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

        <x-ui.section>
            @forelse ($notifications as $notification)
                @php
                    $data = $notification->data;
                    $isResolved = ($data['event_status'] ?? null) === 'cancelled';
                    $url = $data['url'] ?? route('dashboard');
                @endphp
                <x-ui.card
                    padding="p-4 sm:p-5"
                    @class([
                        'relative overflow-hidden before:absolute before:inset-y-4 before:start-0 before:w-1 before:rounded-e-full before:bg-sanmal-brand-yellow-dark' => ! $notification->read_at,
                    ])
                >
                    <article class="flex items-start gap-3">
                        <div @class([
                            'mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-full',
                            'bg-sanmal-surface-secondary text-sanmal-text-muted dark:bg-sanmal-surface-inset-dark' => $notification->read_at,
                            'bg-sanmal-surface-inset text-sanmal-brand-blue dark:bg-sanmal-surface-inset-dark dark:text-sanmal-brand-yellow-dark' => ! $notification->read_at,
                        ])>
                            <flux:icon name="{{ $isResolved ? 'x-circle' : 'bell' }}" class="size-4" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <h2 class="font-semibold text-[var(--sanmal-text-primary)]">{{ $data['title'] ?? __('Notifikasi sistem') }}</h2>
                                @if (! $notification->read_at)
                                    <x-ui.badge color="sky">{{ __('Baru') }}</x-ui.badge>
                                @endif
                            </div>
                            <p class="mt-1 text-sm text-[var(--sanmal-text-secondary)]">{{ $data['message'] ?? __('Ada pembaruan pada akun Anda.') }}</p>
                            @if ($isResolved)
                                <p class="mt-2 text-xs font-medium text-[var(--sanmal-text-muted)]">{{ __('Tidak memerlukan tindakan — pengajuan terkait telah dibatalkan.') }}</p>
                            @endif
                            <div class="mt-3 flex flex-wrap items-center justify-between gap-2">
                                <time class="text-xs text-[var(--sanmal-text-muted)]" datetime="{{ $notification->created_at->toISOString() }}" title="{{ $notification->created_at->format('d M Y H:i:s') }}">
                                    {{ __('Masuk') }} {{ $notification->created_at->timezone(config('app.timezone'))->translatedFormat('d M Y, H:i') }}
                                </time>
                                @if (! empty($data['resolved_at']))
                                    <time class="text-xs text-[var(--sanmal-text-muted)]" datetime="{{ $data['resolved_at'] }}" title="{{ $data['resolved_at'] }}">
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
                    </article>
                </x-ui.card>
            @empty
                <x-ui.card padding="p-8" class="text-center">
                    <flux:icon name="bell" class="mx-auto size-8 text-sanmal-brand-blue dark:text-sanmal-brand-blue-light" />
                    <flux:heading size="md" class="mt-3">{{ __('Belum ada notifikasi') }}</flux:heading>
                    <flux:text class="mt-1">{{ __('Pemberitahuan sistem akan muncul di halaman ini.') }}</flux:text>
                </x-ui.card>
            @endforelse
            {{ $notifications->links() }}
        </x-ui.section>
    </x-ui.page>
</x-layouts::app>
