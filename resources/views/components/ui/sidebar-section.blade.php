@props([
    'heading',
])

<div {{ $attributes->class('flex flex-col') }}>
    <div class="flex h-8 shrink-0 items-center px-3">
        <span class="text-sm font-medium leading-none text-zinc-400 in-data-flux-sidebar-collapsed-desktop:hidden">
            {{ $heading }}
        </span>

        <flux:separator
            variant="subtle"
            class="mx-auto my-0 hidden w-6 in-data-flux-sidebar-collapsed-desktop:block"
            aria-hidden="true"
            data-test="sidebar-group-separator"
        />
    </div>

    {{ $slot }}
</div>
