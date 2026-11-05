@props([
    'name' => null,
    'id' => null,
    'label' => null,
    'description' => null,
    'value' => null,
    'type' => 'text',
    'showError' => true,
])

@php
    $controlId = $id ?? $name;
@endphp

<x-ui.field
    :name="$name"
    :id="$controlId"
    :label="$label"
    :description="$description"
    :show-error="$showError"
>
    <flux:input
        :name="$name"
        :id="$controlId"
        :type="$type"
        :value="$value"
        {{ $attributes->class(['w-full']) }}
    />
</x-ui.field>
