<x-layouts::auth :title="__('Ajukan Reset Password')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Ajukan reset password')" :description="__('Masukkan email akun aktif. Permintaan akan ditinjau administrator.')"/>
        <x-auth-session-status class="text-center" :status="session('status')" />
        <form method="POST" action="{{ route('password-reset.request.store') }}" class="flex flex-col gap-5">
            @csrf
            <x-ui.input name="email" type="email" :label="__('Email akun')" :value="old('email')" required autocomplete="email"/>
            <x-ui.button type="submit" variant="primary" class="w-full">{{ __('Kirim permintaan') }}</x-ui.button>
        </form>
        <div class="text-center">
            <flux:link :href="route('login')">{{ __('Kembali ke login') }}</flux:link>
        </div>
    </div>
</x-layouts::auth>
