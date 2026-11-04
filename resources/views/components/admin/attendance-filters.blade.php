@props(['filters' => [], 'values' => [], 'options' => []])

@php
    $activeFilterCount = collect($values)->filter(fn ($value) => filled($value))->count();
@endphp

<div data-active-filter-count="{{ $activeFilterCount }}" class="grid w-full min-w-0 grid-cols-1 items-end gap-x-3 gap-y-2 sm:grid-cols-2 xl:grid-cols-4">
    <div class="col-span-full flex items-center justify-between gap-2 pb-1">
        <h3 class="text-sm font-semibold text-[var(--sanmal-text-primary)]">{{ __('Filter absensi') }}</h3>
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
