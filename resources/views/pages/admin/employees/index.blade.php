<x-layouts::app :title="__('Manajemen Pegawai')">
    <x-ui.page x-data="{ filterOpen: false }">
        <x-admin.page-header
            :title="__('Manajemen Pegawai')"
            :description="__('Kelola identitas dan status akun pegawai.')"
        >
            <div class="flex w-full flex-wrap justify-end gap-2">
                <x-ui.button href="{{ route('admin.password-reset-requests.index') }}" variant="outline">
                    {{ __('Permintaan reset password') }}
                </x-ui.button>
                <x-ui.filter-toggle controls="employee-filter-dialog" :count="(int) request()->filled('q') + (int) request()->filled('status')" />
                @if (auth()->user()?->isSuperadmin())
                    <x-ui.button href="{{ route('admin.employees.create') }}" variant="primary" icon="plus">
                        {{ __('Tambah pegawai') }}
                    </x-ui.button>
                @endif
            </div>
        </x-admin.page-header>

        <x-admin.flash-message />

        <x-ui.filter-panel id="employee-filter-dialog" :title="__('Filter pegawai')">
            <form
                method="GET"
                class="grid gap-3 sm:grid-cols-2 lg:grid-cols-[minmax(0,1fr)_12rem_auto] lg:items-end"
            >
                <x-ui.input
                    name="q"
                    :value="request('q')"
                    :label="__('Cari pegawai')"
                    placeholder="Nama, email, nomor pegawai"
                />

                <x-ui.select name="status" :label="__('Status akun')" :value="request('status')">
                    <flux:select.option value="">{{ __('Semua') }}</flux:select.option>
                    <flux:select.option value="active">{{ __('Aktif') }}</flux:select.option>
                    <flux:select.option value="pending">{{ __('Menunggu aktivasi') }}</flux:select.option>
                    <flux:select.option value="inactive">{{ __('Nonaktif') }}</flux:select.option>
                </x-ui.select>

                <div class="flex gap-2 sm:col-span-2 sm:justify-end lg:col-span-1">
                    <x-ui.button
                        type="submit"
                        variant="outline"
                        size="base"
                        icon="magnifying-glass"
                        class="flex-1 sm:flex-none"
                    >
                        {{ __('Filter') }}
                    </x-ui.button>

                    @if (request()->filled('q') || request()->filled('status'))
                        <x-ui.button
                            href="{{ route('admin.employees.index') }}"
                            variant="ghost"
                            size="base"
                            class="flex-1 sm:flex-none"
                        >
                            {{ __('Atur ulang') }}
                        </x-ui.button>
                    @endif
                </div>
            </form>
        </x-ui.filter-panel>

        @if ($employees->count())
            <p class="text-sm text-[var(--sanmal-text-muted)]">
                {{ __('Menampilkan :from–:to dari :total pegawai', [
                    'from' => $employees->firstItem(),
                    'to' => $employees->lastItem(),
                    'total' => $employees->total(),
                ]) }}
            </p>

            <div class="grid grid-cols-1 gap-3 lg:grid-cols-2 xl:hidden">
                @foreach ($employees as $employee)
                    <article class="min-w-0">
                        <x-ui.card padding="p-4" class="flex h-full min-w-0 flex-col gap-3">
                            <div class="flex min-w-0 items-start justify-between gap-3">
                                <div class="flex min-w-0 items-center gap-3">
                                    <x-ui.avatar :name="$employee->user->name" size="sm" />

                                    <div class="min-w-0">
                                        <h2 class="break-words font-semibold text-[var(--sanmal-text-primary)]">
                                            {{ $employee->user->name }}
                                        </h2>
                                        <p class="mt-0.5 break-all text-sm text-[var(--sanmal-text-muted)]">
                                            {{ $employee->user->email }}
                                        </p>
                                    </div>
                                </div>

                                <x-admin.employee-status-badge :user="$employee->user" class="shrink-0" />
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

                            <div class="mt-auto border-t border-sanmal-border pt-3 dark:border-sanmal-border-dark">
                                <x-ui.button
                                    size="sm"
                                    variant="outline"
                                    class="w-full"
                                    href="{{ route('admin.employees.edit', $employee) }}"
                                >
                                    {{ __('Edit pegawai') }}
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
                                <th scope="col" class="px-4 py-3">{{ __('Pegawai') }}</th>
                                <th scope="col" class="px-4 py-3">{{ __('Nomor') }}</th>
                                <th scope="col" class="px-4 py-3">{{ __('Jabatan') }}</th>
                                <th scope="col" class="px-4 py-3">{{ __('Status') }}</th>
                                <th scope="col" class="px-4 py-3"><span class="sr-only">{{ __('Aksi') }}</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-sanmal-border dark:divide-sanmal-border-dark">
                            @foreach ($employees as $employee)
                                <tr>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-3">
                                            <x-ui.avatar :name="$employee->user->name" size="sm" />

                                            <div class="min-w-0">
                                                <p class="font-medium text-[var(--sanmal-text-primary)]">{{ $employee->user->name }}</p>
                                                <p class="break-all text-[var(--sanmal-text-muted)]">{{ $employee->user->email }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="max-w-40 break-words px-4 py-3">{{ $employee->employee_number ?: '—' }}</td>
                                    <td class="max-w-56 break-words px-4 py-3">{{ $employee->title?->name ?: '—' }}</td>
                                    <td class="whitespace-nowrap px-4 py-3">
                                        <x-admin.employee-status-badge :user="$employee->user" />
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right">
                                        <x-ui.button size="sm" variant="outline" href="{{ route('admin.employees.edit', $employee) }}">
                                            {{ __('Edit') }}
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
