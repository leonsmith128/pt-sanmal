<x-layouts::app :title="__('Manajemen Pegawai')">
    <div class="mx-auto flex w-full max-w-7xl flex-col gap-4">
        <x-admin.page-header
            :title="__('Manajemen Pegawai')"
            :description="__('Kelola identitas dan status akun pegawai.')"
        >
            <x-ui.button href="{{ route('admin.employees.create') }}" variant="primary" icon="plus">
                {{ __('Tambah pegawai') }}
            </x-ui.button>
        </x-admin.page-header>

        <x-admin.flash-message />

        <x-admin.filter-panel>
            <form method="GET" class="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-end">
                <div class="w-full sm:min-w-64 sm:flex-1">
                    <flux:input name="q" value="{{ request('q') }}" :label="__('Cari pegawai')" placeholder="Nama, email, nomor pegawai" />
                </div>
                <div class="w-full sm:w-48">
                    <flux:select name="status" :label="__('Status akun')" :value="request('status')">
                        <flux:select.option value="">{{ __('Semua') }}</flux:select.option>
                        <flux:select.option value="active">{{ __('Aktif') }}</flux:select.option>
                        <flux:select.option value="inactive">{{ __('Nonaktif') }}</flux:select.option>
                    </flux:select>
                </div>
                <x-ui.button type="submit" variant="outline">{{ __('Filter') }}</x-ui.button>
            </form>
        </x-admin.filter-panel>

        @if ($employees->count())
            <div class="grid grid-cols-1 gap-3 lg:grid-cols-2 xl:hidden">
                @foreach ($employees as $employee)
                    <article class="flex min-w-0 flex-col gap-3 rounded-xl border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-900">
                        <div class="flex min-w-0 items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h2 class="break-words font-semibold text-zinc-900 dark:text-zinc-100">
                                    {{ $employee->user->name }}
                                </h2>
                                <p class="mt-0.5 break-all text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ $employee->user->email }}
                                </p>
                            </div>
                            <x-ui.badge :color="$employee->user->is_active ? 'green' : 'zinc'">
                                {{ $employee->user->is_active ? __('Aktif') : __('Nonaktif') }}
                            </x-ui.badge>
                        </div>

                        <dl class="grid grid-cols-2 gap-x-3 gap-y-2 border-t border-zinc-100 pt-3 text-sm dark:border-zinc-800">
                            <x-ui.metadata-item
                                :label="__('Nomor pegawai')"
                                label-class="text-xs text-zinc-500 dark:text-zinc-400"
                                value-class="mt-0.5 break-words font-medium text-zinc-800 dark:text-zinc-200"
                            >{{ $employee->employee_number ?: '—' }}</x-ui.metadata-item>
                            <x-ui.metadata-item
                                :label="__('Jabatan')"
                                label-class="text-xs text-zinc-500 dark:text-zinc-400"
                                value-class="mt-0.5 break-words font-medium text-zinc-800 dark:text-zinc-200"
                            >{{ $employee->title?->name ?: '—' }}</x-ui.metadata-item>
                        </dl>

                        <div class="flex justify-end border-t border-zinc-100 pt-2 dark:border-zinc-800">
                            <x-ui.button size="sm" variant="outline" href="{{ route('admin.employees.edit', $employee) }}">
                                {{ __('Ubah pegawai') }}
                            </x-ui.button>
                        </div>
                    </article>
                @endforeach
            </div>

            <div
                class="hidden overflow-x-auto overscroll-x-contain rounded-xl border border-zinc-200 dark:border-zinc-700 xl:block"
                role="region"
                aria-label="{{ __('Tabel manajemen pegawai; geser horizontal untuk melihat seluruh kolom') }}"
                tabindex="0"
            >
                <table class="min-w-[52rem] w-full text-sm">
                    <thead class="bg-zinc-50 text-left dark:bg-zinc-900">
                        <tr>
                            <th scope="col" class="px-3 py-2">{{ __('Pegawai') }}</th>
                            <th scope="col" class="px-3 py-2">{{ __('Nomor') }}</th>
                            <th scope="col" class="px-3 py-2">{{ __('Jabatan') }}</th>
                            <th scope="col" class="px-3 py-2">{{ __('Status') }}</th>
                            <th scope="col" class="px-3 py-2"><span class="sr-only">{{ __('Aksi') }}</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach ($employees as $employee)
                            <tr>
                                <td class="px-3 py-2">
                                    <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $employee->user->name }}</p>
                                    <p class="break-all text-zinc-500 dark:text-zinc-400">{{ $employee->user->email }}</p>
                                </td>
                                <td class="px-3 py-2">{{ $employee->employee_number ?: '—' }}</td>
                                <td class="px-3 py-2">{{ $employee->title?->name ?: '—' }}</td>
                                <td class="px-3 py-2">
                                    <x-ui.badge :color="$employee->user->is_active ? 'green' : 'zinc'">
                                        {{ $employee->user->is_active ? __('Aktif') : __('Nonaktif') }}
                                    </x-ui.badge>
                                </td>
                                <td class="px-3 py-2 text-right">
                                    <x-ui.button size="sm" variant="outline" href="{{ route('admin.employees.edit', $employee) }}">
                                        {{ __('Ubah') }}
                                    </x-ui.button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="rounded-xl border border-dashed border-zinc-300 px-4 py-10 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                {{ __('Belum ada data pegawai yang sesuai dengan filter.') }}
            </div>
        @endif

        {{ $employees->links() }}
    </div>
</x-layouts::app>
