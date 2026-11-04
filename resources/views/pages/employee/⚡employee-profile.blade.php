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

<x-ui.page max-width="3xl" gap="6">
    <x-ui.page-header
        :title="__('Profil Pegawai')"
        :description="__('Kelola foto profil dan informasi kontak Anda. Data identitas dan kepegawaian dikelola oleh admin.')"
    />

    @php
        $profilePhotoSrc = $photo?->isPreviewable()
            ? $photo->temporaryUrl()
            : auth()->user()->profilePhotoUrl();
    @endphp

    <x-ui.card padding="p-6">
        <div class="flex flex-col gap-5 sm:flex-row sm:items-center">
            @if ($profilePhotoSrc)
                <x-ui.photo-preview
                    :src="$profilePhotoSrc"
                    :alt="__('Foto profil')"
                    :title="__('Foto profil')"
                    :trigger-label="__('Perbesar foto profil')"
                    class="rounded-full"
                >
                    <x-ui.avatar
                        :src="$profilePhotoSrc"
                        :name="auth()->user()->name"
                        :initials="auth()->user()->initials()"
                        size="xl"
                        class="cursor-zoom-in"
                    />
                </x-ui.photo-preview>
            @else
                <x-ui.avatar
                    :name="auth()->user()->name"
                    :initials="auth()->user()->initials()"
                    size="xl"
                />
            @endif

            <div class="min-w-0 flex-1">
                <flux:heading>{{ auth()->user()->name }}</flux:heading>
                <flux:text class="mt-1">{{ auth()->user()->email }}</flux:text>
            </div>
        </div>

        <form wire:submit="saveProfilePhoto" class="mt-5 space-y-4">
            <x-ui.field name="photo" id="photo" :label="__('Foto profil')">
                <div x-data class="flex items-center gap-3">
                    <input
                        x-ref="photoInput"
                        id="photo"
                        name="photo"
                        type="file"
                        wire:model="photo"
                        accept="image/jpeg,image/png,image/webp"
                        class="sr-only"
                    />

                    <x-ui.button
                        type="button"
                        variant="outline"
                        size="xs"
                        x-on:click="$refs.photoInput.click()"
                    >
                        Pilih file
                    </x-ui.button>
                </div>
            </x-ui.field>

            <div class="flex flex-wrap gap-2">
                <x-ui.button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="photo,saveProfilePhoto">
                    Simpan foto
                </x-ui.button>

                @if (auth()->user()->profile_photo_path)
                    <x-ui.button type="button" variant="ghost" wire:click="removeProfilePhoto" wire:confirm="Hapus foto profil Anda?">
                        Hapus foto
                    </x-ui.button>
                @endif
            </div>

            <flux:text wire:loading wire:target="photo,saveProfilePhoto" class="text-sm text-zinc-500 dark:text-zinc-400">Mengunggah foto...</flux:text>
        </form>
    </x-ui.card>

    <x-ui.card padding="p-6">
        <flux:heading size="lg">Informasi pribadi</flux:heading>
        <form wire:submit="saveContactDetails" class="mt-5 space-y-5">
            <x-ui.input
                name="phone"
                :label="__('Nomor telepon')"
                wire:model="phone"
                type="tel"
                autocomplete="tel"
                placeholder="Contoh: 081234567890"
                maxlength="30"
            />

            @if (auth()->user()->employee)
                <dl class="grid gap-4 sm:grid-cols-2">
                    <x-ui.metadata-item label="Nomor pegawai">{{ auth()->user()->employee->employee_number ?: 'Belum diatur' }}</x-ui.metadata-item>
                    <x-ui.metadata-item label="Jabatan">{{ auth()->user()->employee->title?->name ?? 'Belum diatur' }}</x-ui.metadata-item>
                    <x-ui.metadata-item label="Status kepegawaian">{{ auth()->user()->employee->employment_status ?: 'Belum diatur' }}</x-ui.metadata-item>
                    <x-ui.metadata-item label="Tanggal bergabung">{{ auth()->user()->employee->joined_at?->format('d M Y') ?? 'Belum diatur' }}</x-ui.metadata-item>
                </dl>
            @else
                <flux:callout icon="information-circle" variant="warning">
                    Data profil kepegawaian belum terhubung ke akun ini. Hubungi admin jika Anda memerlukan data tersebut.
                </flux:callout>
            @endif

            <div class="flex justify-end">
                <x-ui.button type="submit" variant="primary" :disabled="! auth()->user()->employee">
                    Simpan perubahan
                </x-ui.button>
            </div>
        </form>
    </x-ui.card>
</x-ui.page>
