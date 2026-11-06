{{-- Shared export trigger and confirmation modal. --}}
@props([
    'href' => null,
    'options' => [],
    'summary' => null,
    'warning' => null,
    'label' => null,
    'confirmTitle' => null,
    'modalId' => 'export-confirm-dialog',
])

@php
    $label ??= __('Export');
@endphp

@if ($options !== [])
    <div
        x-data="{ exportConfirmOpen: false, exportUrl: '', exportDescription: '' }"
        {{ $attributes->class(['w-full sm:w-auto']) }}
    >
        <flux:dropdown position="bottom" align="end">
            <x-ui.button size="sm" variant="outline" class="w-full justify-center whitespace-nowrap sm:w-auto" icon="arrow-down-tray">
                <span class="inline-flex items-center gap-2 whitespace-nowrap">
                    {{ $label }}
                    <flux:icon name="chevron-down" variant="micro" />
                </span>
            </x-ui.button>

            <flux:menu>
                @foreach ($options as $option)
                    @if ($option['divider_before'] ?? false)
                        <flux:menu.separator />
                    @endif

                    <flux:menu.item
                        :href="$option['href']"
                        :data-description="$option['description']"
                        x-on:click.prevent="exportUrl = $el.href; exportDescription = $el.dataset.description; exportConfirmOpen = true"
                        :icon="$option['icon']"
                    >
                        {{ $option['label'] }}
                    </flux:menu.item>
                @endforeach
            </flux:menu>
        </flux:dropdown>

        <x-ui.modal id="{{ $modalId }}" :title="$confirmTitle ?? __('Konfirmasi export')" model="exportConfirmOpen" max-width="max-w-lg">
            <div class="space-y-3">
                <p class="text-sm text-[var(--sanmal-text-secondary)]" x-text="exportDescription"></p>
                @if ($summary)
                    <p class="text-sm text-[var(--sanmal-text-secondary)]">{{ $summary }}</p>
                @endif
                @if ($warning)
                    <p class="rounded-lg border border-sanmal-brand-yellow/30 bg-sanmal-brand-yellow/10 px-3 py-2 text-sm text-[var(--sanmal-text-primary)]">{{ $warning }}</p>
                @endif
            </div>

            <x-slot:actions>
                <x-ui.button type="button" variant="outline" class="w-full sm:w-auto" x-on:click="exportConfirmOpen = false">{{ __('Batal') }}</x-ui.button>
                <x-ui.button href="#" x-bind:href="exportUrl" variant="primary" class="w-full sm:w-auto" x-on:click="exportConfirmOpen = false">
                    {{ __('Konfirmasi dan export') }}
                </x-ui.button>
            </x-slot:actions>
        </x-ui.modal>
    </div>
@elseif ($href !== null)
    <x-ui.button :href="$href" variant="outline" icon="arrow-down-tray" {{ $attributes }}>
        {{ $label }}
    </x-ui.button>
@else
    <x-ui.button type="submit" variant="outline" icon="arrow-down-tray" {{ $attributes }}>
        {{ $label }}
    </x-ui.button>
@endif
