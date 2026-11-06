{{-- Filter form displayed inside the shared modal shell. --}}
@props([
    'id',
    'title',
    'description' => null,
    'model' => 'filterOpen',
])

<x-ui.modal :id="$id" :title="$title" :model="$model" max-width="max-w-3xl" {{ $attributes }}>
    @if ($description)
        <p class="mb-4 text-sm text-[var(--sanmal-text-secondary)]">{{ $description }}</p>
    @endif

    {{ $slot }}
</x-ui.modal>
