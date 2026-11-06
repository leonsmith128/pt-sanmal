{{-- Filter form displayed inside the shared modal shell. --}}
@props([
    'id',
    'title',
    'description' => null,
    'model' => 'filterOpen',
])

<x-ui.modal :id="$id" :title="$title" :model="$model" max-width="max-w-3xl" {{ $attributes }}>
    @if ($description || isset($aside))
        <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
            @if ($description)
                <p class="text-sm text-[var(--sanmal-text-secondary)]">{{ $description }}</p>
            @endif
            @isset($aside)
                {{ $aside }}
            @endisset
        </div>
    @endif

    {{ $slot }}
</x-ui.modal>
