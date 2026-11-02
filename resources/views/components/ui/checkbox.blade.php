@props([
    'name' => null,
    'id' => null,
    'label' => null,
    'description' => null,
    'checked' => null,
    'showError' => true,
])

@php
    $controlId = $id ?? $name;
@endphp

<div {{ $attributes->class(['min-w-0']) }}>
    <flux:checkbox
        :name="$name"
        :id="$controlId"
        :label="$label"
        :description="$description"
        :checked="$checked"
    />

    @if ($showError && filled($name))
        <flux:error :name="$name" />
    @endif
</div>
