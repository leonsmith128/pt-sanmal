@props([
    'message' => 'Tidak ada data untuk ditampilkan.',
    'colspan' => null,
])

<x-ui.empty-state :message="$message" :colspan="$colspan" {{ $attributes }}>
    {{ $slot }}
</x-ui.empty-state>
