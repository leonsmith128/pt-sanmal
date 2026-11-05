<x-layouts::app :title="__('Ganti Password Awal')">
    <x-ui.page class="!max-w-xl gap-5">
        <x-admin.page-header
            :title="__('Buat Password Pribadi')"
            :description="__('Untuk keamanan, ganti password sementara sebelum melanjutkan menggunakan aplikasi.')"
        />

        <x-ui.card padding="p-5 sm:p-6">
            <form method="POST" action="{{ route('account.password.first-change.store') }}" class="space-y-4">
                @csrf
                <x-ui.input name="password" type="password" :label="__('Password baru')" required autocomplete="new-password" />
                <x-ui.input name="password_confirmation" type="password" :label="__('Konfirmasi password')" required autocomplete="new-password" />
                <div class="flex justify-end">
                    <x-ui.button type="submit" variant="primary">{{ __('Simpan password') }}</x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </x-ui.page>
</x-layouts::app>
