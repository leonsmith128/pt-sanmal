@props([
    'name' => null,
    'id' => null,
    'label' => null,
    'description' => null,
    'placeholder' => null,
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
    <flux:select
        :name="$name"
        :id="$controlId"
        :placeholder="$placeholder"
        {{ $attributes->class(['w-full']) }}
    >
        {{ $slot }}
    </flux:select>
</x-ui.field>
