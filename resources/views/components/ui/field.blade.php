@props([
    'name' => null,
    'id' => null,
    'label' => null,
    'description' => null,
    'showError' => true,
])

@php
    $controlId = $id ?? $name;
@endphp

<flux:field {{ $attributes->class(['min-w-0']) }}>
    @if (filled($label))
        <flux:label :for="$controlId">{{ $label }}</flux:label>
    @endif

    {{ $slot }}

    @if (filled($description))
        <flux:description>{{ $description }}</flux:description>
    @endif

    @if ($showError && filled($name))
        <flux:error :name="$name" />
    @endif
</flux:field>
