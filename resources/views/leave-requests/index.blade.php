<x-layouts::app :title="'Pusat Permohonan'">
    <div class="mx-auto w-full max-w-6xl space-y-5 px-4 py-4 sm:px-6 sm:py-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <flux:heading size="xl" level="1">{{ __('Pusat Permohonan') }}</flux:heading>
                <flux:text class="mt-1">{{ __('Pantau izin, sakit, cuti, lembur, dan koreksi absensi dari satu tempat.') }}</flux:text>
            </div>
            <div class="grid grid-cols-2 gap-2 sm:flex">
                <flux:button href="{{ route('overtime-requests.index') }}" variant="ghost" icon="clock">{{ __('Lembur') }}</flux:button>
                <flux:button href="{{ route('leave-requests.create') }}" variant="primary" icon="plus">{{ __('Buat permohonan') }}</flux:button>
            </div>
        </div>

        @if (session('status'))
            <div role="status" class="rounded-xl border border-green-200 bg-green-50 p-3 text-sm text-green-900 dark:border-green-800/60 dark:bg-green-950/40 dark:text-green-300">
                {{ session('status') }}
            </div>
        @endif

        <section class="rounded-2xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900 sm:p-5">
            <div class="mb-4 flex items-center justify-between gap-3">
                <div>
                    <flux:heading size="lg">{{ __('Filter permohonan') }}</flux:heading>
                    <flux:text class="mt-1 text-sm">{{ __('Tanggal memfilter periode permohonan, bukan tanggal pengajuan.') }}</flux:text>
                </div>
                <span class="shrink-0 text-sm text-zinc-500">{{ number_format($totalCount) }} {{ __('hasil') }}</span>
            </div>

            <form method="GET" action="{{ route('leave-requests.index') }}" class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-6">
                <div class="sm:col-span-2 lg:col-span-2">
                    <flux:field>
                        <flux:label>{{ __('Cari') }}</flux:label>
                        <flux:input name="q" value="{{ $filters['q'] }}" placeholder="{{ __('Nomor, jenis, atau alasan') }}" clearable />
                    </flux:field>
                </div>
                <flux:field>
                    <flux:label>{{ __('Jenis') }}</flux:label>
                    <flux:select name="type">
                        <flux:select.option value="">{{ __('Semua jenis') }}</flux:select.option>
                        <flux:select.option value="izin" :selected="$filters['type'] === 'izin'">{{ __('Izin') }}</flux:select.option>
                        <flux:select.option value="sakit" :selected="$filters['type'] === 'sakit'">{{ __('Sakit') }}</flux:select.option>
                        <flux:select.option value="cuti" :selected="$filters['type'] === 'cuti'">{{ __('Cuti') }}</flux:select.option>
                        <flux:select.option value="lembur" :selected="$filters['type'] === 'lembur'">{{ __('Lembur') }}</flux:select.option>
                        <flux:select.option value="koreksi_absensi" :selected="$filters['type'] === 'koreksi_absensi'">{{ __('Koreksi absensi') }}</flux:select.option>
                    </flux:select>
                </flux:field>
                <flux:field>
                    <flux:label>{{ __('Status') }}</flux:label>
                    <flux:select name="status">
                        <flux:select.option value="">{{ __('Semua status') }}</flux:select.option>
                        <flux:select.option value="pending" :selected="$filters['status'] === 'pending'">{{ __('Menunggu') }}</flux:select.option>
                        <flux:select.option value="approved" :selected="$filters['status'] === 'approved'">{{ __('Disetujui') }}</flux:select.option>
                        <flux:select.option value="rejected" :selected="$filters['status'] === 'rejected'">{{ __('Ditolak') }}</flux:select.option>
                        <flux:select.option value="cancelled" :selected="$filters['status'] === 'cancelled'">{{ __('Dibatalkan') }}</flux:select.option>
                    </flux:select>
                </flux:field>
                <flux:field>
                    <flux:label>{{ __('Dari tanggal') }}</flux:label>
                    <flux:input type="date" name="from" value="{{ $filters['from'] }}" />
                </flux:field>
                <flux:field>
                    <flux:label>{{ __('Sampai tanggal') }}</flux:label>
                    <flux:input type="date" name="to" value="{{ $filters['to'] }}" />
                </flux:field>
                <div class="flex flex-col gap-2 sm:col-span-2 lg:col-span-6 sm:flex-row sm:justify-end">
                    <flux:button href="{{ route('leave-requests.index') }}" variant="ghost" class="w-full sm:w-auto">{{ __('Reset filter') }}</flux:button>
                    <flux:button type="submit" variant="primary" icon="filter" class="w-full sm:w-auto">{{ __('Terapkan filter') }}</flux:button>
                    <flux:button href="{{ route('leave-requests.export', request()->query()) }}" variant="outline" icon="arrow-down-tray" class="w-full sm:w-auto">{{ __('Ekspor CSV') }}</flux:button>
                </div>
            </form>
        </section>

        @if ($requests->isEmpty())
            <div class="rounded-2xl border border-dashed border-zinc-300 px-5 py-12 text-center dark:border-zinc-700">
                <flux:icon name="inbox" class="mx-auto size-9 text-zinc-400" />
                <flux:heading size="md" class="mt-3">{{ __('Tidak ada permohonan yang cocok') }}</flux:heading>
                <flux:text class="mt-1">{{ __('Coba ubah filter atau buat permohonan baru.') }}</flux:text>
                <div class="mt-4"><flux:button href="{{ route('leave-requests.index') }}" variant="ghost">{{ __('Hapus semua filter') }}</flux:button></div>
            </div>
        @else
            {{-- Mobile: cards avoid horizontal scrolling and keep primary status visible. --}}
            <div class="space-y-3 md:hidden">
                @foreach ($requests as $item)
                    <article class="space-y-3 rounded-2xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-xs font-medium text-zinc-500">{{ $item->reference }} · {{ $item->submitted_at?->format('d/m/Y') }}</p>
                                <h2 class="mt-1 font-semibold text-zinc-900 dark:text-zinc-100">{{ $item->type_label }}</h2>
                            </div>
                            <flux:badge :color="match($item->status) {'approved' => 'green', 'rejected' => 'red', 'cancelled' => 'zinc', default => 'amber'}">{{ $item->status_label }}</flux:badge>
                        </div>
                        <div class="text-sm text-zinc-600 dark:text-zinc-300">
                            <p><span class="font-medium text-zinc-800 dark:text-zinc-100">{{ __('Periode') }}:</span>
                                {{ $item->period_start ? \Illuminate\Support\Carbon::parse($item->period_start)->format('d/m/Y') : '—' }}
                                @if ($item->period_end && $item->period_end !== $item->period_start)
                                    – {{ \Illuminate\Support\Carbon::parse($item->period_end)->format('d/m/Y') }}
                                @endif
                            </p>
                            <p class="mt-2 whitespace-pre-line break-words">{{ $item->summary }}</p>
                            @if ($item->operational_status)
                                <p class="mt-2"><span class="font-medium">{{ __('Proses lembur') }}:</span> {{ $item->operational_status }}</p>
                            @endif
                        </div>
                        @if ($item->reviewer || $item->decision_note)
                            <div class="rounded-lg bg-zinc-50 p-3 text-sm dark:bg-zinc-800">
                                @if ($item->reviewer)
                                    <p><span class="font-medium">{{ __('Diproses oleh') }}:</span> {{ $item->reviewer }} @if ($item->decided_at) · {{ $item->decided_at->format('d/m/Y H:i') }} @endif</p>
                                @endif
                                @if ($item->decision_note)
                                    <p class="mt-1 break-words">{{ $item->decision_note }}</p>
                                @endif
                            </div>
                        @endif
                        @if ($item->cancelable && $item->cancel_route)
                            <form method="POST" action="{{ $item->cancel_route }}" onsubmit="return confirm('Batalkan permohonan ini?')">
                                @csrf @method('PATCH')
                                <flux:button type="submit" variant="ghost" size="sm" icon="x-circle" class="w-full">{{ __('Batalkan permohonan') }}</flux:button>
                            </form>
                        @endif
                    </article>
                @endforeach
            </div>

            {{-- Desktop/tablet: comparable columns, with operational overtime status kept separate. --}}
            <div class="hidden overflow-hidden rounded-2xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 md:block">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-zinc-50 text-xs uppercase tracking-wide text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">
                            <tr>
                                <th class="px-4 py-3">{{ __('Nomor / Jenis') }}</th>
                                <th class="px-4 py-3">{{ __('Periode') }}</th>
                                <th class="px-4 py-3">{{ __('Ringkasan') }}</th>
                                <th class="px-4 py-3">{{ __('Status') }}</th>
                                <th class="px-4 py-3">{{ __('Keputusan') }}</th>
                                <th class="px-4 py-3 text-right">{{ __('Aksi') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                            @foreach ($requests as $item)
                                <tr class="align-top">
                                    <td class="px-4 py-4">
                                        <p class="text-xs text-zinc-500">{{ $item->reference }}</p>
                                        <p class="mt-1 font-medium">{{ $item->type_label }}</p>
                                        <p class="mt-1 text-xs text-zinc-500">{{ $item->submitted_at?->format('d/m/Y H:i') }}</p>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-4">
                                        {{ $item->period_start ? \Illuminate\Support\Carbon::parse($item->period_start)->format('d/m/Y') : '—' }}
                                        @if ($item->period_end && $item->period_end !== $item->period_start)
                                            <span class="text-zinc-500">– {{ \Illuminate\Support\Carbon::parse($item->period_end)->format('d/m/Y') }}</span>
                                        @endif
                                    </td>
                                    <td class="max-w-sm px-4 py-4">
                                        <p class="whitespace-pre-line break-words">{{ $item->summary }}</p>
                                        @if ($item->operational_status)
                                            <p class="mt-2 text-xs text-zinc-500">{{ __('Proses lembur') }}: {{ $item->operational_status }}</p>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-4">
                                        <flux:badge :color="match($item->status) {'approved' => 'green', 'rejected' => 'red', 'cancelled' => 'zinc', default => 'amber'}">{{ $item->status_label }}</flux:badge>
                                    </td>
                                    <td class="min-w-48 px-4 py-4 text-sm">
                                        @if ($item->reviewer)<p class="font-medium">{{ $item->reviewer }}</p>@endif
                                        @if ($item->decided_at)<p class="text-xs text-zinc-500">{{ $item->decided_at->format('d/m/Y H:i') }}</p>@endif
                                        @if ($item->decision_note)<p class="mt-1 whitespace-pre-line break-words text-xs text-zinc-600 dark:text-zinc-300">{{ $item->decision_note }}</p>@endif
                                        @if (! $item->reviewer && ! $item->decision_note)<span class="text-zinc-400">—</span>@endif
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-4 text-right">
                                        @if ($item->cancelable && $item->cancel_route)
                                            <form method="POST" action="{{ $item->cancel_route }}" onsubmit="return confirm('Batalkan permohonan ini?')">
                                                @csrf @method('PATCH')
                                                <flux:button type="submit" variant="ghost" size="sm">{{ __('Batalkan') }}</flux:button>
                                            </form>
                                        @else
                                            <span class="text-zinc-400">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div>{{ $requests->links() }}</div>
        @endif
    </div>
</x-layouts::app>
