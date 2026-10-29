<?php

use App\Models\Employee;
use Flux\Flux;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Profil Pegawai')] class extends Component {
    use WithFileUploads;

    public string $phone = '';

    #[Validate('nullable|image|mimes:jpg,jpeg,png,webp|max:2048')]
    public $photo = null;

    public function mount(): void
    {
        $this->phone = auth()->user()->employee?->phone ?? '';
    }

    public function saveContactDetails(): void
    {
        $validated = $this->validate([
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        $employee = auth()->user()->employee;

        abort_unless($employee instanceof Employee, 404);

        $employee->phone = $validated['phone'] ?: null;
        $employee->save();

        Flux::toast(variant: 'success', text: 'Nomor telepon berhasil diperbarui.');
    }

    public function saveProfilePhoto(): void
    {
        $this->validate();

        if (! $this->photo) {
            return;
        }

        $user = auth()->user();
        $oldPath = $user->profile_photo_path;
        $newPath = $this->photo->store('profile-photos', 'public');

        $user->profile_photo_path = $newPath;
        $user->save();

        if ($oldPath) {
            Storage::disk('public')->delete($oldPath);
        }

        $this->reset('photo');

        Flux::toast(variant: 'success', text: 'Foto profil berhasil diperbarui.');
    }

    public function removeProfilePhoto(): void
    {
        $user = auth()->user();
        $oldPath = $user->profile_photo_path;

        $user->profile_photo_path = null;
        $user->save();

        if ($oldPath) {
            Storage::disk('public')->delete($oldPath);
        }

        $this->reset('photo');

        Flux::toast(variant: 'success', text: 'Foto profil berhasil dihapus.');
    }
}; ?>

<section class="w-full space-y-6">
    <div>
        <flux:heading size="xl">Profil Pegawai</flux:heading>
        <flux:subheading class="mt-1">
            Kelola foto profil dan informasi kontak Anda. Data identitas dan kepegawaian dikelola oleh admin.
        </flux:subheading>
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex flex-col gap-5 sm:flex-row sm:items-center">
            <flux:avatar
                :src="$photo?->temporaryUrl() ?? auth()->user()->profilePhotoUrl()"
                :name="auth()->user()->name"
                :initials="auth()->user()->initials()"
                size="xl"
            />

            <div class="min-w-0 flex-1">
                <flux:heading>{{ auth()->user()->name }}</flux:heading>
                <flux:text class="mt-1">{{ auth()->user()->email }}</flux:text>
                <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    JPG, PNG, atau WebP. Maksimal 2 MB.
                </flux:text>
            </div>
        </div>

        <form wire:submit="saveProfilePhoto" class="mt-5 space-y-4">
            <flux:input
                type="file"
                wire:model="photo"
                :label="__('Foto profil')"
                accept="image/jpeg,image/png,image/webp"
            />
            @error('photo')
                <flux:text class="text-sm text-red-600 dark:text-red-400">{{ $message }}</flux:text>
            @enderror

            <div class="flex flex-wrap gap-2">
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="photo,saveProfilePhoto">
                    Simpan foto
                </flux:button>

                @if (auth()->user()->profile_photo_path)
                    <flux:button type="button" variant="ghost" wire:click="removeProfilePhoto" wire:confirm="Hapus foto profil Anda?">
                        Hapus foto
                    </flux:button>
                @endif
            </div>

            <flux:progress.indeterminate wire:loading wire:target="photo,saveProfilePhoto" />
        </form>
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="lg">Informasi pribadi</flux:heading>
        <flux:subheading class="mt-1">Nomor telepon dapat Anda perbarui sendiri.</flux:subheading>

        <form wire:submit="saveContactDetails" class="mt-5 space-y-5">
            <flux:input
                :label="__('Nomor telepon')"
                wire:model="phone"
                type="tel"
                autocomplete="tel"
                placeholder="Contoh: 081234567890"
                maxlength="30"
            />

            @if (auth()->user()->employee)
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <flux:label>Nomor pegawai</flux:label>
                        <flux:text class="mt-1">{{ auth()->user()->employee->employee_number ?: 'Belum diatur' }}</flux:text>
                    </div>
                    <div>
                        <flux:label>Jabatan</flux:label>
                        <flux:text class="mt-1">{{ auth()->user()->employee->title?->name ?? 'Belum diatur' }}</flux:text>
                    </div>
                    <div>
                        <flux:label>Status kepegawaian</flux:label>
                        <flux:text class="mt-1">{{ auth()->user()->employee->employment_status ?: 'Belum diatur' }}</flux:text>
                    </div>
                    <div>
                        <flux:label>Tanggal bergabung</flux:label>
                        <flux:text class="mt-1">{{ auth()->user()->employee->joined_at?->format('d M Y') ?? 'Belum diatur' }}</flux:text>
                    </div>
                </div>
            @else
                <flux:callout icon="information-circle" variant="warning">
                    Data profil kepegawaian belum terhubung ke akun ini. Hubungi admin jika Anda memerlukan data tersebut.
                </flux:callout>
            @endif

            <div class="flex justify-end">
                <flux:button type="submit" variant="primary" :disabled="! auth()->user()->employee">
                    Simpan perubahan
                </flux:button>
            </div>
        </form>
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="lg">Pengaturan akun</flux:heading>
        <flux:subheading class="mt-1">Email dan kata sandi dikelola pada halaman pengaturan akun.</flux:subheading>

        <div class="mt-4 flex flex-wrap gap-2">
            <flux:button :href="route('profile.edit')" wire:navigate icon="mail">Ubah email</flux:button>
            <flux:button :href="route('security.edit')" wire:navigate icon="key-round">Ubah kata sandi</flux:button>
        </div>
    </div>
</section>
