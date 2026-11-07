<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-dvh bg-sanmal-surface-page antialiased dark:bg-sanmal-surface-page-dark">
        <div class="relative flex min-h-dvh flex-col items-center justify-center px-4 py-10 sm:px-6">

            <div aria-hidden="true" class="absolute inset-x-0 top-0 h-1 bg-sanmal-brand-yellow-dark"></div>

            <main class="relative w-full max-w-md">
                <a href="{{ route('home') }}" class="mb-6 flex justify-center rounded-md focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-sanmal-brand-blue dark:focus-visible:outline-sanmal-brand-blue-light" wire:navigate>
                    <img src="{{ asset('images/logo-sanmal.png') }}" alt="SanMal" class="h-12 w-auto object-contain" />
                </a>

                <div class="rounded-xl border border-sanmal-border bg-sanmal-surface-card p-5 dark:border-sanmal-border-dark dark:bg-sanmal-surface-card-dark sm:p-8">
                    {{ $slot }}
                </div>

                <p class="mt-5 text-center text-xs text-sanmal-text-muted">{{ __('Portal pegawai') }}</p>
            </main>
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
