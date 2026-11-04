@props([
    'selfie' => null,
])

@if ($selfie && $selfie->isPreviewable())
    <div class="relative w-full">
        <x-ui.photo-preview
            :src="$selfie->temporaryUrl()"
            :alt="__('Foto absensi')"
            :title="__('Pratinjau foto absensi')"
            :trigger-label="__('Perbesar pratinjau foto absensi')"
            :download-url="$selfie->temporaryUrl()"
            :download-name="'foto-absensi.'.($selfie->guessExtension() ?: 'jpg')"
            class="rounded-lg"
        >
            <img
                src="{{ $selfie->temporaryUrl() }}"
                alt="{{ __('Pratinjau foto absensi') }}"
                class="block h-auto w-auto max-h-48 max-w-full rounded-lg object-contain"
            />
        </x-ui.photo-preview>

        <div class="mt-2">
            <flux:button type="button" size="sm" icon="arrow-path" wire:click="retakeSelfie">{{ __('Foto ulang') }}</flux:button>
        </div>
    </div>
@endif
