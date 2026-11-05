<x-layouts::auth :title="__('Buat Password Baru')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Buat password baru')" :description="__('Permintaan reset Anda telah disetujui. Tetapkan password baru untuk akun SanMal.')"/>
        <form method="POST" action="{{ route('password-reset.store', ['token' => $token]) }}" class="flex flex-col gap-5">
            @csrf
            <x-ui.input name="password" type="password" :label="__('Password baru')" required autocomplete="new-password"/>
            <x-ui.input name="password_confirmation" type="password" :label="__('Konfirmasi password')" required autocomplete="new-password"/>
            <x-ui.button type="submit" variant="primary" class="w-full">{{ __('Simpan password') }}</x-ui.button>
        </form>
    </div>
</x-layouts::auth>
