@props([
    'name' => null,
    'id' => null,
    'label' => null,
    'description' => null,
    'rows' => 4,
    'resize' => 'vertical',
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
    <flux:textarea
        :name="$name"
        :id="$controlId"
        :rows="$rows"
        :resize="$resize"
        {{ $attributes->class(['w-full']) }}
    >{{ $slot }}</flux:textarea>
</x-ui.field>
