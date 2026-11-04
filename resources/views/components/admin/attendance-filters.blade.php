@props(['filters' => [], 'values' => [], 'options' => []])

@php
    $activeFilterCount = collect($values)->filter(fn ($value) => filled($value))->count();
@endphp

<div x-data="{ filtersOpen: false }" data-active-filter-count="{{ $activeFilterCount }}" class="w-full min-w-0">
    <button
        type="button"
        class="flex w-full items-center justify-between gap-3 rounded-lg border border-sanmal-border bg-sanmal-surface-primary px-3 py-2.5 text-sm text-[var(--sanmal-text-primary)] dark:border-sanmal-border-dark dark:bg-sanmal-surface-primary-dark"
        x-on:click="filtersOpen = ! filtersOpen"
        x-bind:aria-expanded="filtersOpen.toString()"
        aria-controls="admin-attendance-filter-controls"
    >
        <span class="flex min-w-0 items-center gap-2">
            <span class="font-semibold">{{ __('Filter absensi') }}</span>
            <span class="rounded-full bg-sanmal-surface-secondary px-2 py-0.5 text-xs text-[var(--sanmal-text-secondary)] dark:bg-sanmal-surface-secondary-dark">
                {{ $activeFilterCount }} {{ __('aktif') }}
            </span>
        </span>
        <flux:icon name="chevron-down" variant="micro" class="shrink-0 transition-transform" x-bind:class="filtersOpen ? 'rotate-180' : ''" />
    </button>

    <div id="admin-attendance-filter-controls" x-cloak x-show="filtersOpen" x-collapse class="mt-3 grid w-full min-w-0 grid-cols-1 items-end gap-x-2 gap-y-3 sm:grid-cols-2 lg:grid-cols-5">
    <div class="col-span-full flex items-center justify-between gap-2 pb-1">
        <span class="text-xs text-[var(--sanmal-text-secondary)]">{{ $activeFilterCount }} {{ __('filter aktif') }}</span>
    </div>

    @if (in_array('date', $filters, true))
        <x-ui.input name="dateFrom" size="sm" type="date" wire:model.live="dateFrom" :label="__('Dari tanggal')" class="w-full min-w-0" />
        <x-ui.input name="dateTo" size="sm" type="date" wire:model.live="dateTo" :label="__('Sampai tanggal')" class="w-full min-w-0" />
    @endif
    @if (in_array('client', $filters, true))
        <x-ui.select name="clientId" size="sm" wire:model.live="clientId" :label="__('Client')" class="w-full min-w-0">
            <flux:select.option value="">{{ __('Semua Client') }}</flux:select.option>
            @foreach ($options['clients'] ?? [] as $option)
                <flux:select.option value="{{ $option['id'] }}">{{ $option['label'] }}</flux:select.option>
            @endforeach
        </x-ui.select>
    @endif
    @if (in_array('location', $filters, true))
        <x-ui.select name="locationId" size="sm" wire:model.live="locationId" :label="__('Location')" class="w-full min-w-0">
            <flux:select.option value="">{{ __('Semua Location') }}</flux:select.option>
            @foreach ($options['locations'] ?? [] as $option)
                <flux:select.option value="{{ $option['id'] }}">{{ $option['label'] }}</flux:select.option>
            @endforeach
        </x-ui.select>
    @endif
    @if (in_array('project', $filters, true))
        <x-ui.select name="projectId" size="sm" wire:model.live="projectId" :label="__('Project')" class="w-full min-w-0">
            <flux:select.option value="">{{ __('Semua Project') }}</flux:select.option>
            @foreach ($options['projects'] ?? [] as $option)
                <flux:select.option value="{{ $option['id'] }}">{{ $option['label'] }}</flux:select.option>
            @endforeach
        </x-ui.select>
    @endif
    @if (in_array('employee', $filters, true))
        <x-ui.select name="employeeId" size="sm" wire:model.live="employeeId" :label="__('Pegawai')" class="w-full min-w-0">
            <flux:select.option value="">{{ __('Semua pegawai') }}</flux:select.option>
            @foreach ($options['employees'] ?? [] as $option)
                <flux:select.option value="{{ $option['id'] }}">{{ $option['label'] }}</flux:select.option>
            @endforeach
        </x-ui.select>
    @endif
    @if (in_array('search', $filters, true))
        <x-ui.input name="search" size="sm" wire:model.live.debounce.300ms="search" :label="__('Pencarian')" :placeholder="__('Nama / nomor / catatan')" class="w-full min-w-0" />
    @endif
    @if (in_array('attendance_type', $filters, true))
        <x-ui.select name="attendanceType" size="sm" wire:model.live="attendanceType" :label="__('Jenis absensi')" class="w-full min-w-0">
            <flux:select.option value="">{{ __('Semua jenis') }}</flux:select.option>
            @foreach ($options['attendance_types'] ?? [] as $option)
                <flux:select.option value="{{ $option['value'] }}">{{ $option['label'] }}</flux:select.option>
            @endforeach
        </x-ui.select>
    @endif
    @if (in_array('work_type', $filters, true))
        <x-ui.select name="workType" size="sm" wire:model.live="workType" :label="__('Jenis kerja')" class="w-full min-w-0">
            <flux:select.option value="">{{ __('Semua jenis kerja') }}</flux:select.option>
            @foreach ($options['work_types'] ?? [] as $option)
                <flux:select.option value="{{ $option['value'] }}">{{ $option['label'] }}</flux:select.option>
            @endforeach
        </x-ui.select>
    @endif
    </div>
</div>
