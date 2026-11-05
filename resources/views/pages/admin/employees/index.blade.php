<x-layouts::app :title="__('Manajemen Pegawai')">
    <x-ui.page>
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
                    <x-ui.input name="q" :value="request('q')" :label="__('Cari pegawai')" placeholder="Nama, email, nomor pegawai" />
                </div>
                <div class="w-full sm:w-48">
                    <x-ui.select name="status" :label="__('Status akun')" :value="request('status')">
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
                    <article class="min-w-0">
                    <x-ui.card padding="p-3" class="flex h-full min-w-0 flex-col gap-3">
                        <div class="flex min-w-0 items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h2 class="break-words font-semibold text-[var(--sanmal-text-primary)]">
                                    {{ $employee->user->name }}
                                </h2>
                                <p class="mt-0.5 break-all text-sm text-[var(--sanmal-text-muted)]">
                                    {{ $employee->user->email }}
                                </p>
                            </div>
                            <x-ui.badge class="shrink-0" :color="$employee->user->is_active ? 'green' : 'zinc'">
                                {{ $employee->user->is_active ? __('Aktif') : __('Nonaktif') }}
                            </x-ui.badge>
                        </div>

                        <dl class="grid grid-cols-2 gap-x-3 gap-y-2 border-t border-sanmal-border pt-3 text-sm dark:border-sanmal-border-dark">
                            <x-ui.metadata-item
                                :label="__('Nomor pegawai')"
                                label-class="text-xs text-[var(--sanmal-text-muted)]"
                                value-class="mt-0.5 break-words font-medium text-[var(--sanmal-text-primary)]"
                            >{{ $employee->employee_number ?: '—' }}</x-ui.metadata-item>
                            <x-ui.metadata-item
                                :label="__('Jabatan')"
                                label-class="text-xs text-[var(--sanmal-text-muted)]"
                                value-class="mt-0.5 break-words font-medium text-[var(--sanmal-text-primary)]"
                            >{{ $employee->title?->name ?: '—' }}</x-ui.metadata-item>
                        </dl>

                        <div class="flex justify-end border-t border-sanmal-border pt-2 dark:border-sanmal-border-dark">
                            <x-ui.button size="sm" variant="outline" href="{{ route('admin.employees.edit', $employee) }}">
                                {{ __('Ubah pegawai') }}
                            </x-ui.button>
                        </div>
                    </x-ui.card>
                </article>
                @endforeach
            </div>

            <x-ui.card padding="p-0" class="hidden overflow-hidden xl:block">
            <div
                class="overflow-x-auto overscroll-x-contain"
                role="region"
                aria-label="{{ __('Tabel manajemen pegawai; geser horizontal untuk melihat seluruh kolom') }}"
                tabindex="0"
            >
                <table class="min-w-[52rem] w-full text-sm">
                    <thead class="bg-sanmal-surface-secondary text-left dark:bg-sanmal-surface-secondary-dark">
                        <tr>
                            <th scope="col" class="px-3 py-2">{{ __('Pegawai') }}</th>
                            <th scope="col" class="px-3 py-2">{{ __('Nomor') }}</th>
                            <th scope="col" class="px-3 py-2">{{ __('Jabatan') }}</th>
                            <th scope="col" class="px-3 py-2">{{ __('Status') }}</th>
                            <th scope="col" class="px-3 py-2"><span class="sr-only">{{ __('Aksi') }}</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-sanmal-border dark:divide-sanmal-border-dark">
                        @foreach ($employees as $employee)
                            <tr>
                                <td class="px-3 py-2">
                                    <p class="font-medium text-[var(--sanmal-text-primary)]">{{ $employee->user->name }}</p>
                                    <p class="break-all text-[var(--sanmal-text-muted)]">{{ $employee->user->email }}</p>
                                </td>
                                <td class="max-w-40 break-words px-3 py-2">{{ $employee->employee_number ?: '—' }}</td>
                                <td class="max-w-56 break-words px-3 py-2">{{ $employee->title?->name ?: '—' }}</td>
                                <td class="whitespace-nowrap px-3 py-2">
                                    <x-ui.badge :color="$employee->user->is_active ? 'green' : 'zinc'">
                                        {{ $employee->user->is_active ? __('Aktif') : __('Nonaktif') }}
                                    </x-ui.badge>
                                </td>
                                <td class="whitespace-nowrap px-3 py-2 text-right">
                                    <x-ui.button size="sm" variant="outline" href="{{ route('admin.employees.edit', $employee) }}">
                                        {{ __('Ubah') }}
                                    </x-ui.button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            </x-ui.card>
        @else
            <x-ui.empty-state
                class="!border-dashed !border-sanmal-border !bg-transparent !px-4 !py-10 dark:!border-sanmal-border-dark"
                :message="__('Belum ada data pegawai yang sesuai dengan filter.')"
            />
        @endif

        {{ $employees->links() }}
    </x-ui.page>
</x-layouts::app>
