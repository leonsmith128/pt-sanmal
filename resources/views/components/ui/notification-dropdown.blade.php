@props([
    'notifications',
    'unreadCount' => 0,
])

<details class="group relative">
    <summary
        aria-label="{{ __('Buka notifikasi') }}"
        class="flex size-10 cursor-pointer list-none items-center justify-center rounded-full text-zinc-600 transition hover:bg-zinc-100 hover:text-zinc-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sanmal-600 dark:text-zinc-300 dark:hover:bg-zinc-800 dark:hover:text-white [&::-webkit-details-marker]:hidden"
    >
        <flux:icon name="bell" class="size-5" />
        @if ($unreadCount > 0)
            <span class="absolute right-1 top-1 flex min-w-4 items-center justify-center rounded-full bg-red-600 px-1 text-[10px] font-semibold leading-4 text-white">
                {{ $unreadCount > 99 ? '99+' : $unreadCount }}
            </span>
        @endif
    </summary>

    <div class="absolute right-0 z-50 mt-2 w-[min(22rem,calc(100vw-2rem))] origin-top-right rounded-xl border border-zinc-200 bg-white shadow-xl ring-1 ring-black/5 dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex items-center justify-between gap-3 border-b border-zinc-100 px-4 py-3 dark:border-zinc-800">
            <h2 class="font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Notifikasi') }}</h2>
            @if ($unreadCount > 0)
                <x-ui.badge color="sky">{{ __(':count belum dibaca', ['count' => $unreadCount]) }}</x-ui.badge>
            @endif
        </div>

        @forelse ($notifications as $notification)
            @php
                $data = $notification->data;
                $isResolved = ($data['event_status'] ?? null) === 'cancelled';
            @endphp
            <form method="POST" action="{{ route('notifications.read', $notification) }}">
                @csrf
                @method('PATCH')
                <button
                    type="submit"
                    class="flex w-full items-start gap-3 border-b border-zinc-100 px-4 py-3 text-left transition last:border-b-0 hover:bg-zinc-50 focus-visible:bg-zinc-50 focus-visible:outline-none dark:border-zinc-800 dark:hover:bg-zinc-800/70 dark:focus-visible:bg-zinc-800/70 {{ $notification->read_at ? '' : 'bg-sky-50/70 dark:bg-sky-950/20' }}"
                >
                    <span class="mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-full {{ $notification->read_at ? 'bg-zinc-100 text-zinc-500 dark:bg-zinc-800' : 'bg-sky-100 text-sky-700 dark:bg-sky-900 dark:text-sky-300' }}">
                        <flux:icon name="{{ $isResolved ? 'x-circle' : 'bell' }}" class="size-4" />
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="flex items-start justify-between gap-2">
                            <span class="line-clamp-1 text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ $data['title'] ?? __('Notifikasi sistem') }}</span>
                            @if (! $notification->read_at)
                                <span aria-label="{{ __('Belum dibaca') }}" class="mt-1 size-2 shrink-0 rounded-full bg-sky-600"></span>
                            @endif
                        </span>
                        <span class="mt-1 line-clamp-2 block text-xs leading-5 text-zinc-600 dark:text-zinc-300">{{ $data['message'] ?? __('Ada pembaruan pada akun Anda.') }}</span>
                        <span class="mt-1 block text-[11px] text-zinc-500">{{ $notification->created_at->diffForHumans() }}</span>
                    </span>
                </button>
            </form>
        @empty
            <div class="px-4 py-8 text-center">
                <flux:icon name="bell" class="mx-auto size-7 text-zinc-400" />
                <p class="mt-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">{{ __('Belum ada notifikasi') }}</p>
                <p class="mt-1 text-xs text-zinc-500">{{ __('Pemberitahuan terbaru akan muncul di sini.') }}</p>
            </div>
        @endforelse

        <a
            href="{{ route('notifications.index') }}"
            class="block border-t border-zinc-100 px-4 py-3 text-center text-sm font-semibold text-sanmal-700 transition hover:bg-zinc-50 hover:underline dark:border-zinc-800 dark:text-sanmal-300 dark:hover:bg-zinc-800/70"
        >
            {{ __('Lihat semua notifikasi') }}
        </a>
    </div>
</details>
