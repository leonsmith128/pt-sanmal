<x-layouts::app :title="__('Permintaan Reset Password')">
    <x-ui.page>
        <x-admin.page-header
            :title="__('Permintaan Reset Password')"
            :description="__('Tinjau permintaan reset password pegawai dan catat keputusan admin.')"
        />
        <x-admin.flash-message />

        <x-ui.card padding="p-0" class="overflow-hidden">
            <div class="divide-y divide-sanmal-border dark:divide-sanmal-border-dark">
                @forelse ($requests as $resetRequest)
                    <div class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
                        <div class="min-w-0 space-y-1">
                            <p class="font-medium">{{ $resetRequest->user->name }}</p>
                            <p class="break-all text-sm text-[var(--sanmal-text-muted)]">{{ $resetRequest->user->email }}</p>
                            <p class="text-xs text-[var(--sanmal-text-muted)]">
                                {{ __('Status: :status · Diajukan :date', ['status' => $resetRequest->status, 'date' => $resetRequest->created_at->format('d M Y H:i')]) }}
                            </p>
                            @if ($resetRequest->review_note)
                                <p class="text-sm">{{ $resetRequest->review_note }}</p>
                            @endif
                        </div>

                        @if ($resetRequest->status === 'pending')
                            <div class="flex flex-wrap gap-2">
                                <form method="POST" action="{{ route('admin.password-reset-requests.approve', $resetRequest) }}">
                                    @csrf
                                    <x-ui.button type="submit" variant="primary">{{ __('Setujui') }}</x-ui.button>
                                </form>
                                <form method="POST" action="{{ route('admin.password-reset-requests.reject', $resetRequest) }}" class="flex gap-2">
                                    @csrf
                                    <input type="hidden" name="review_note" value="Ditolak oleh administrator."/>
                                    <x-ui.button type="submit" variant="outline">{{ __('Tolak') }}</x-ui.button>
                                </form>
                            </div>
                        @endif
                    </div>
                @empty
                    <x-ui.empty-state :message="__('Belum ada permintaan reset password.')"/>
                @endforelse
            </div>
        </x-ui.card>

        {{ $requests->links() }}
    </x-ui.page>
</x-layouts::app>
