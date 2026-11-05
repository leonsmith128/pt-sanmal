@props([
    'title',
    'description',
])

<div class="flex w-full flex-col items-start text-start">
    <span class="mb-4 flex size-10 items-center justify-center rounded-xl bg-sanmal-surface-inset text-sanmal-brand-blue dark:bg-sanmal-surface-inset-dark dark:text-sanmal-brand-blue-light">
        <flux:icon.shield-check class="size-5" />
    </span>
    <flux:heading size="xl" level="1">{{ $title }}</flux:heading>
    <flux:subheading class="mt-1 max-w-sm leading-relaxed">{{ $description }}</flux:subheading>
</div>
