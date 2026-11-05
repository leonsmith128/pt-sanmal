<x-layouts::app :title="__('Permintaan Reset Password')">
    <x-ui.page>
        <x-admin.page-header
            :title="__('Permintaan Reset Password')"
            :description="__('Tinjau permintaan reset password pegawai dan catat keputusan admin.')"
        >
            <x-ui.button href="{{ route('admin.employees.index') }}" variant="outline" icon="arrow-left">
                {{ __('Manajemen pegawai') }}
            </x-ui.button>
        </x-admin.page-header>
        <x-admin.flash-message />

        <x-ui.card padding="p-0" class="overflow-hidden">
            <div class="divide-y divide-sanmal-border dark:divide-sanmal-border-dark">
                @forelse ($requests as $resetRequest)
                    <div class="flex flex-col gap-3 p-4 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0 flex-1 space-y-2">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="font-semibold">{{ $resetRequest->user->name }}</p>
                                @php
                                    $statusLabel = match ($resetRequest->status) {
                                        'pending' => __('Menunggu tinjauan'),
                                        'approved' => __('Disetujui'),
                                        'rejected' => __('Ditolak'),
                                        'used' => __('Selesai'),
                                        'expired' => __('Kedaluwarsa / dibatalkan'),
                                        default => ucfirst($resetRequest->status),
                                    };
                                    $statusColor = match ($resetRequest->status) {
                                        'pending' => 'amber',
                                        'approved' => 'green',
                                        'used' => 'green',
                                        'rejected', 'expired' => 'zinc',
                                        default => 'zinc',
                                    };
                                @endphp
                                <x-ui.badge :color="$statusColor">{{ $statusLabel }}</x-ui.badge>
                            </div>
                            <p class="break-all text-sm text-[var(--sanmal-text-muted)]">{{ $resetRequest->user->email }}</p>
                            <dl class="grid gap-2 text-xs text-[var(--sanmal-text-muted)] sm:grid-cols-2">
                                <div>
                                    <dt>{{ __('Diajukan') }}</dt>
                                    <dd class="mt-0.5 font-medium text-[var(--sanmal-text-primary)]">{{ $resetRequest->created_at->format('d M Y H:i') }}</dd>
                                </div>
                                @if ($resetRequest->reviewed_at)
                                    <div>
                                        <dt>{{ __('Ditinjau') }}</dt>
                                        <dd class="mt-0.5 font-medium text-[var(--sanmal-text-primary)]">
                                            {{ $resetRequest->reviewed_at->format('d M Y H:i') }}
                                            @if ($resetRequest->reviewer)
                                                · {{ $resetRequest->reviewer->name }}
                                            @endif
                                        </dd>
                                    </div>
                                @endif
                                @if ($resetRequest->expires_at && $resetRequest->status === 'approved')
                                    <div>
                                        <dt>{{ __('Tautan reset berlaku sampai') }}</dt>
                                        <dd class="mt-0.5 font-medium text-[var(--sanmal-text-primary)]">{{ $resetRequest->expires_at->format('d M Y H:i') }}</dd>
                                    </div>
                                @endif
                            </dl>
                            @if ($resetRequest->review_note)
                                <div class="rounded-lg bg-sanmal-surface-secondary p-3 text-sm dark:bg-sanmal-surface-secondary-dark">
                                    <p class="text-xs font-medium text-[var(--sanmal-text-muted)]">{{ __('Catatan admin') }}</p>
                                    <p class="mt-1 whitespace-pre-line">{{ $resetRequest->review_note }}</p>
                                </div>
                            @endif
                        </div>

                        @if ($resetRequest->status === 'pending')
                            <div class="grid w-full shrink-0 gap-3 sm:max-w-sm sm:grid-cols-2">
                                <form method="POST" action="{{ route('admin.password-reset-requests.approve', $resetRequest) }}" class="flex flex-col gap-2">
                                    @csrf
                                    <x-ui.textarea name="review_note" :label="__('Catatan (opsional)')" rows="2" />
                                    <x-ui.button type="submit" variant="primary">{{ __('Setujui & kirim tautan') }}</x-ui.button>
                                </form>
                                <form method="POST" action="{{ route('admin.password-reset-requests.reject', $resetRequest) }}" class="flex flex-col gap-2">
                                    @csrf
                                    <x-ui.textarea name="review_note" :label="__('Alasan penolakan')" rows="2" required />
                                    <x-ui.button type="submit" variant="outline">{{ __('Tolak permintaan') }}</x-ui.button>
                                </form>
                            </div>
                        @endif
                    </div>
                @empty
                    <x-ui.empty-state :message="__('Belum ada permintaan reset password.')" />
                @endforelse
            </div>
        </x-ui.card>

        {{ $requests->links() }}
    </x-ui.page>
</x-layouts::app>