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

<div {{ $attributes->only('class')->class(['min-w-0']) }}>
    <flux:checkbox
        :name="$name"
        :id="$controlId"
        :label="$label"
        :description="$description"
        :checked="$checked"
        {{ $attributes->except('class') }}
    />

    @if ($showError && filled($name))
        <flux:error :name="$name" />
    @endif
</div>
