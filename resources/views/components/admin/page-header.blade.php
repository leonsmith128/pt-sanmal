@props(['title', 'description' => null])

<x-ui.page-header :title="$title" :description="$description" {{ $attributes }}>
    {{ $slot }}
</x-ui.page-header>
