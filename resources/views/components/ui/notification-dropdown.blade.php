@props([
    'notifications',
    'unreadCount' => 0,
])

@php
    $panelId = 'notification-panel-'.\Illuminate\Support\Str::random(6);
@endphp

{{--
    Popover notifikasi. Menutup saat klik di luar, tombol Escape (fokus kembali ke pemicu),
    dan setelah navigasi Livewire. Jangan kembali ke <details>: elemen itu tidak punya
    penutupan klik-luar bawaan.
--}}
<div
    x-data="{ open: false }"
    x-on:click.outside="open = false"
    x-on:keydown.escape.window="if (open) { open = false; $refs.trigger.focus() }"
    x-on:livewire:navigated.window="open = false"
    {{ $attributes->class(['relative']) }}
>
    <button
        type="button"
        x-ref="trigger"
        x-on:click="open = ! open"
        x-bind:aria-expanded="open.toString()"
        x-bind:class="open ? 'bg-sanmal-surface-secondary text-sanmal-text-primary dark:bg-sanmal-surface-inset-dark' : ''"
        aria-expanded="false"
        aria-haspopup="dialog"
        aria-controls="{{ $panelId }}"
        aria-label="{{ __('Buka notifikasi') }}"
        class="relative flex size-11 items-center justify-center rounded-full text-sanmal-text-secondary transition-colors hover:bg-sanmal-surface-secondary hover:text-sanmal-text-primary focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--sanmal-focus)] dark:hover:bg-sanmal-surface-inset-dark"
    >
        <flux:icon name="bell" class="size-5" />
        @if ($unreadCount > 0)
            <span aria-hidden="true" class="absolute right-1.5 top-1.5 flex min-w-4 items-center justify-center rounded-full bg-sanmal-danger px-1 text-xs font-semibold leading-4 text-white tabular-nums">
                {{ $unreadCount > 99 ? '99+' : $unreadCount }}
            </span>
            <span class="sr-only">{{ __(':count belum dibaca', ['count' => $unreadCount]) }}</span>
        @endif
    </button>

    <div
        id="{{ $panelId }}"
        role="dialog"
        aria-label="{{ __('Notifikasi') }}"
        x-show="open"
        x-transition:enter="transition duration-150 ease-out motion-reduce:transition-none"
        x-transition:enter-start="-translate-y-1 scale-95 opacity-0"
        x-transition:enter-end="translate-y-0 scale-100 opacity-100"
        x-transition:leave="transition duration-100 ease-in motion-reduce:transition-none"
        x-transition:leave-start="scale-100 opacity-100"
        x-transition:leave-end="scale-95 opacity-0"
        style="display: none;"
        class="fixed right-2 top-16 z-[100] flex max-h-[calc(100dvh-5rem)] w-[min(22rem,calc(100vw-1rem))] origin-top-right flex-col overflow-hidden rounded-xl border border-sanmal-border bg-sanmal-surface-card shadow-xl dark:border-sanmal-border-dark dark:bg-sanmal-surface-elevated-dark lg:absolute lg:right-0 lg:top-full lg:mt-2 lg:max-h-[min(32rem,calc(100dvh-6rem))] lg:w-[min(22rem,calc(100vw-2rem))]"
    >
        <div class="flex shrink-0 items-center justify-between gap-3 border-b border-sanmal-border px-4 py-3 dark:border-sanmal-border-dark">
            <h2 class="text-base font-semibold text-sanmal-text-primary">{{ __('Notifikasi') }}</h2>
            @if ($unreadCount > 0)
                <x-ui.badge color="sky">{{ __(':count belum dibaca', ['count' => $unreadCount]) }}</x-ui.badge>
            @endif
        </div>

        <div class="min-h-0 flex-1 overflow-y-auto overscroll-contain">
            @forelse ($notifications as $notification)
                @php
                    $data = $notification->data;
                    $isResolved = ($data['event_status'] ?? null) === 'cancelled';
                    $isUnread = ! $notification->read_at;
                @endphp
                <form method="POST" action="{{ route('notifications.read', $notification) }}" class="border-b border-sanmal-border last:border-b-0 dark:border-sanmal-border-dark">
                    @csrf
                    @method('PATCH')
                    <button
                        type="submit"
                        class="relative flex min-h-16 w-full items-start gap-3 px-4 py-3 text-left transition-colors hover:bg-sanmal-surface-secondary focus-visible:bg-sanmal-surface-secondary focus-visible:outline-none dark:hover:bg-white/5 dark:focus-visible:bg-white/5 {{ $isUnread ? 'bg-accent/5' : '' }}"
                    >
                        @if ($isUnread)
                            <span aria-hidden="true" class="absolute inset-y-0 left-0 w-0.5 bg-accent"></span>
                        @endif
                        <span class="mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-full {{ $isUnread ? 'bg-accent/10 text-accent' : 'bg-sanmal-surface-secondary text-sanmal-text-muted dark:bg-sanmal-surface-inset-dark' }}">
                            <flux:icon name="{{ $isResolved ? 'x-circle' : 'bell' }}" class="size-4" />
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="flex items-start justify-between gap-2">
                                <span class="line-clamp-1 text-sm font-semibold text-sanmal-text-primary">{{ $data['title'] ?? __('Notifikasi sistem') }}</span>
                                @if ($isUnread)
                                    <span role="img" aria-label="{{ __('Belum dibaca') }}" class="mt-1.5 size-2 shrink-0 rounded-full bg-accent"></span>
                                @endif
                            </span>
                            <span class="mt-0.5 line-clamp-2 block text-sm leading-5 text-sanmal-text-secondary">{{ $data['message'] ?? __('Ada pembaruan pada akun Anda.') }}</span>
                            <time datetime="{{ $notification->created_at->toIso8601String() }}" class="mt-1 block text-xs text-sanmal-text-muted">{{ $notification->created_at->diffForHumans() }}</time>
                        </span>
                    </button>
                </form>
            @empty
                <div class="px-4 py-10 text-center">
                    <span class="mx-auto flex size-10 items-center justify-center rounded-full bg-sanmal-surface-secondary text-sanmal-text-muted dark:bg-sanmal-surface-inset-dark">
                        <flux:icon name="bell" class="size-5" />
                    </span>
                    <p class="mt-3 text-sm font-medium text-sanmal-text-primary">{{ __('Belum ada notifikasi') }}</p>
                    <p class="mt-1 text-sm text-sanmal-text-muted">{{ __('Pemberitahuan terbaru akan muncul di sini.') }}</p>
                </div>
            @endforelse
        </div>

        <a
            href="{{ route('notifications.index') }}"
            class="block shrink-0 border-t border-sanmal-border px-4 py-3.5 text-center text-sm font-medium text-accent transition-colors hover:bg-sanmal-surface-secondary hover:underline focus-visible:bg-sanmal-surface-secondary focus-visible:outline-none dark:border-sanmal-border-dark dark:hover:bg-white/5 dark:focus-visible:bg-white/5"
        >
            {{ __('Lihat semua notifikasi') }}
        </a>
    </div>
</div>
