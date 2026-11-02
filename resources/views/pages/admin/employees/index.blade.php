<x-layouts::app :title="__('Manajemen Pegawai')">
    <div class="mx-auto flex w-full max-w-7xl flex-col gap-4">
        <x-admin.page-header
            :title="__('Manajemen Pegawai')"
            :description="__('Kelola identitas dan status akun pegawai.')"
        >
            <flux:button href="{{ route('admin.employees.create') }}" variant="primary" icon="plus">
                {{ __('Tambah pegawai') }}
            </flux:button>
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
                <flux:button type="submit">{{ __('Filter') }}</flux:button>
            </form>
        </x-admin.filter-panel>

        @if ($employees->count())
            <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:hidden">
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
                            <flux:badge :color="$employee->user->is_active ? 'green' : 'zinc'">
                                {{ $employee->user->is_active ? __('Aktif') : __('Nonaktif') }}
                            </flux:badge>
                        </div>

                        <dl class="grid grid-cols-2 gap-x-3 gap-y-2 border-t border-zinc-100 pt-3 text-sm dark:border-zinc-800">
                            <div class="min-w-0">
                                <dt class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Nomor pegawai') }}</dt>
                                <dd class="mt-0.5 break-words font-medium text-zinc-800 dark:text-zinc-200">{{ $employee->employee_number ?: '—' }}</dd>
                            </div>
                            <div class="min-w-0">
                                <dt class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Jabatan') }}</dt>
                                <dd class="mt-0.5 break-words font-medium text-zinc-800 dark:text-zinc-200">{{ $employee->title?->name ?: '—' }}</dd>
                            </div>
                        </dl>

                        <div class="flex justify-end border-t border-zinc-100 pt-2 dark:border-zinc-800">
                            <flux:button size="sm" variant="outline" href="{{ route('admin.employees.edit', $employee) }}">
                                {{ __('Ubah pegawai') }}
                            </flux:button>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="hidden overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700 xl:block">
                <table class="w-full text-sm">
                    <thead class="bg-zinc-50 text-left dark:bg-zinc-900">
                        <tr>
                            <th class="px-3 py-2">{{ __('Pegawai') }}</th>
                            <th class="px-3 py-2">{{ __('Nomor') }}</th>
                            <th class="px-3 py-2">{{ __('Jabatan') }}</th>
                            <th class="px-3 py-2">{{ __('Status') }}</th>
                            <th class="px-3 py-2"></th>
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
                                    <flux:badge :color="$employee->user->is_active ? 'green' : 'zinc'">
                                        {{ $employee->user->is_active ? __('Aktif') : __('Nonaktif') }}
                                    </flux:badge>
                                </td>
                                <td class="px-3 py-2 text-right">
                                    <flux:button size="sm" variant="outline" href="{{ route('admin.employees.edit', $employee) }}">
                                        {{ __('Ubah') }}
                                    </flux:button>
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
