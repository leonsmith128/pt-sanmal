<x-layouts::app :title="__('Manajemen Pegawai')">
    <div class="mx-auto flex w-full max-w-6xl flex-col gap-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <flux:heading size="xl">{{ __('Manajemen Pegawai') }}</flux:heading>
                <flux:text>{{ __('Kelola identitas dan status akun pegawai.') }}</flux:text>
            </div>
            <flux:button href="{{ route('admin.employees.create') }}" variant="primary" icon="plus">
                {{ __('Tambah pegawai') }}
            </flux:button>
        </div>


        <x-admin.flash-message />

        <form method="GET" class="flex flex-wrap items-end gap-3">
            <flux:input name="q" value="{{ request('q') }}" :label="__('Cari pegawai')" placeholder="Nama, email, nomor pegawai" />
            <flux:select name="status" :label="__('Status akun')" :value="request('status')">
                <flux:select.option value="">{{ __('Semua') }}</flux:select.option>
                <flux:select.option value="active">{{ __('Aktif') }}</flux:select.option>
                <flux:select.option value="inactive">{{ __('Nonaktif') }}</flux:select.option>
            </flux:select>
            <flux:button type="submit">{{ __('Filter') }}</flux:button>
        </form>

        <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
            <table class="w-full text-sm">
                <thead class="bg-zinc-50 text-left dark:bg-zinc-900">
                    <tr>
                        <th class="p-3">{{ __('Pegawai') }}</th>
                        <th class="p-3">{{ __('Nomor') }}</th>
                        <th class="p-3">{{ __('Jabatan') }}</th>
                        <th class="p-3">{{ __('Status') }}</th>
                        <th class="p-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse ($employees as $employee)
                        <tr>
                            <td class="p-3">
                                <p class="font-medium">{{ $employee->user->name }}</p>
                                <p class="text-zinc-500">{{ $employee->user->email }}</p>
                            </td>
                            <td class="p-3">{{ $employee->employee_number ?: '—' }}</td>
                            <td class="p-3">{{ $employee->title?->name ?: '—' }}</td>
                            <td class="p-3">
                                <flux:badge :color="$employee->user->is_active ? 'green' : 'zinc'">
                                    {{ $employee->user->is_active ? __('Aktif') : __('Nonaktif') }}
                                </flux:badge>
                            </td>
                            <td class="p-3 text-right">
                                <flux:button size="sm" variant="ghost" href="{{ route('admin.employees.edit', $employee) }}">
                                    {{ __('Ubah') }}
                                </flux:button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-8 text-center text-zinc-500">{{ __('Belum ada data pegawai.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $employees->links() }}
    </div>
</x-layouts::app>
