@props([
    'name',
    'initials' => null,
    'src' => null,
    'greeting' => null,
    'description' => null,
    'avatarSize' => 'lg',
])

<header {{ $attributes->class(['flex min-w-0 items-center gap-3 sm:gap-4']) }}>
    <x-ui.avatar
        :name="$name"
        :initials="$initials"
        :src="$src"
        :size="$avatarSize"
        :aria-label="__('Foto profil :name', ['name' => $name])"
    />

    <div class="min-w-0 flex-1">
        @if (filled($greeting))
            <p class="text-sm text-[var(--sanmal-text-secondary)]">{{ $greeting }}</p>
        @endif

        <h1 class="truncate text-xl font-bold tracking-tight text-[var(--sanmal-text-primary)] sm:text-2xl">
            {{ $name }}
        </h1>

        @if (filled($description))
            <p class="mt-1 text-sm text-[var(--sanmal-text-muted)]">
                {{ $description }}
            </p>
        @endif
    </div>

    @isset($actions)
        <div class="shrink-0">
            {{ $actions }}
        </div>
    @endisset
</header>
