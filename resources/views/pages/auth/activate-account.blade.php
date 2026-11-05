<x-layouts::auth :title="__('Aktivasi Akun')">
    <div class="mx-auto w-full max-w-md space-y-6">
        <div class="space-y-2 text-center">
            <h1 class="text-2xl font-semibold">{{ __('Aktivasi akun pegawai') }}</h1>
            <p class="text-sm text-[var(--sanmal-text-muted)]">{{ __('Buat password pribadi untuk menyelesaikan aktivasi akun SanMal.') }}</p>
        </div>

        <form method="POST" action="{{ route('employee-activation.store', ['token' => $token]) }}" class="space-y-4">
            @csrf
            <x-ui.input name="password" type="password" :label="__('Password baru')" required autocomplete="new-password" />
            <x-ui.input name="password_confirmation" type="password" :label="__('Konfirmasi password')" required autocomplete="new-password" />
            <x-ui.button type="submit" variant="primary" class="w-full">{{ __('Aktifkan akun') }}</x-ui.button>
        </form>
    </div>
</x-layouts::auth>
