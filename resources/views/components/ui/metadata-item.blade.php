@props([
    'label',
    'labelClass' => 'text-sm text-zinc-500 dark:text-zinc-400',
    'valueClass' => 'mt-1 text-sm text-zinc-900 dark:text-zinc-100',
])

<div {{ $attributes->class(['min-w-0 break-words']) }}>
    <dt class="{{ $labelClass }}">{{ $label }}</dt>
    <dd class="{{ $valueClass }}">{{ $slot }}</dd>
</div>
