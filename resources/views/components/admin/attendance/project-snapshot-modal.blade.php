@props([
    'modalName',
    'projectCode' => null,
    'projectName',
    'clientCode' => null,
    'clientName' => null,
    'locationName' => null,
])

<flux:modal.trigger :name="$modalName">
    <button
        type="button"
        title="{{ __('Lihat konteks proyek: :project', ['project' => $projectName]) }}"
        {{ $attributes->class(['block max-w-full truncate text-left text-sm font-medium text-sanmal-brand-blue underline decoration-sanmal-brand-blue/30 underline-offset-2 transition hover:decoration-current focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sanmal-brand-blue dark:text-sanmal-brand-yellow-dark dark:decoration-sanmal-brand-yellow-dark/40 dark:focus-visible:outline-sanmal-brand-yellow-dark']) }}
    >
        {{ $projectCode ? $projectCode.' · ' : '' }}{{ $projectName }}
    </button>
</flux:modal.trigger>

<flux:modal :name="$modalName" class="max-w-md">
    <div class="space-y-4">
        <div>
            <flux:heading size="lg">{{ __('Konteks proyek') }}</flux:heading>
            <flux:text class="mt-1 text-sm">{{ __('Data berikut tersimpan saat absensi dicatat.') }}</flux:text>
        </div>

        <dl class="grid gap-3 rounded-lg bg-sanmal-surface-secondary p-4 text-sm dark:bg-sanmal-surface-secondary-dark">
            <x-ui.metadata-item
                :label="__('Proyek')"
                label-class="text-xs text-[var(--sanmal-text-muted)]"
                value-class="mt-1 font-medium text-[var(--sanmal-text-primary)]"
            >
                {{ $projectCode ? $projectCode.' · ' : '' }}{{ $projectName }}
            </x-ui.metadata-item>
            <x-ui.metadata-item
                :label="__('Client')"
                label-class="text-xs text-[var(--sanmal-text-muted)]"
                value-class="mt-1 font-medium text-[var(--sanmal-text-primary)]"
            >
                {{ $clientName ? ($clientCode ? $clientCode.' · ' : '').$clientName : '—' }}
            </x-ui.metadata-item>
            <x-ui.metadata-item
                :label="__('Lokasi')"
                label-class="text-xs text-[var(--sanmal-text-muted)]"
                value-class="mt-1 font-medium text-[var(--sanmal-text-primary)]"
            >
                {{ $locationName ?: '—' }}
            </x-ui.metadata-item>
        </dl>

        <div class="flex justify-end">
            <flux:modal.close>
                <x-ui.button variant="outline">{{ __('Tutup') }}</x-ui.button>
            </flux:modal.close>
        </div>
    </div>
</flux:modal>
