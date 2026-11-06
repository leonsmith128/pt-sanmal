<?php

use App\Enums\UserRole;
use App\Models\Employee;
use App\Models\User;
use App\Support\ExportRangeOptions;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

function rangeOptionsUser(UserRole $role, bool $withEmployee = false): User
{
    $user = User::factory()->create();
    $user->forceFill(['role' => $role, 'is_active' => true])->save();

    if ($withEmployee) {
        Employee::query()->create(['user_id' => $user->id]);
    }

    return $user;
}

/** @return array<string, string> label => rentang "from|to" */
function rangeOptionsParsed(array $options): array
{
    return collect($options)->mapWithKeys(function (array $option): array {
        parse_str((string) parse_url($option['href'], PHP_URL_QUERY), $query);

        return [$option['label'] => ($query['from'] ?? '').'|'.($query['to'] ?? '')];
    })->all();
}

it('builds today, this week, and this month ranges in WITA with fixed filters', function () {
    $options = ExportRangeOptions::make(
        'admin.kehadiran.export',
        ['client_id' => 5, 'search' => '', 'project_id' => null],
        today: Carbon::parse('2026-11-06 23:30:00', 'Asia/Makassar'),
    );

    expect(rangeOptionsParsed($options))->toBe([
        'Hari ini' => '2026-11-06|2026-11-06',
        'Minggu ini' => '2026-11-02|2026-11-08',
        'Bulan ini' => '2026-11-01|2026-11-30',
    ]);

    parse_str((string) parse_url($options[0]['href'], PHP_URL_QUERY), $query);

    expect($query)->toHaveKey('client_id', '5')
        ->not->toHaveKeys(['search', 'project_id']);
});

it('adds the page date filter as a custom option and hides it when it duplicates a preset', function () {
    $today = Carbon::parse('2026-11-06', 'Asia/Makassar');

    $custom = ExportRangeOptions::make('admin.kehadiran.export', customFrom: '2026-10-30', customTo: '2026-11-02', today: $today);
    $duplicate = ExportRangeOptions::make('admin.kehadiran.export', customFrom: '2026-11-01', customTo: '2026-11-30', today: $today);

    expect(rangeOptionsParsed($custom))->toHaveKey('Sesuai filter tanggal', '2026-10-30|2026-11-02')
        ->and($custom[3]['divider_before'])->toBeTrue()
        ->and($duplicate)->toHaveCount(3);
});

it('uses the fallback option only when there is no date range and a fallback is given', function () {
    $today = Carbon::parse('2026-11-06', 'Asia/Makassar');

    $with = ExportRangeOptions::make('admin.kehadiran.export', customFallback: 'Tanpa filter tanggal.', today: $today);
    $without = ExportRangeOptions::make('admin.kehadiran.export', today: $today);

    expect($with)->toHaveCount(4)
        ->and($with[3]['description'])->toBe('Tanpa filter tanggal.')
        ->and($with[3]['href'])->not->toContain('from=')
        ->and($without)->toHaveCount(3);
});

it('exports the daily recap for an explicit date range and keeps the month parameter working', function () {
    $employeeUser = User::factory()->create(['name' => 'Pegawai Rentang Rekap']);
    Employee::query()->create(['user_id' => $employeeUser->id]);
    $admin = rangeOptionsUser(UserRole::Admin);

    $text = xlsxContents($this->actingAs($admin)->get(route('admin.daily-attendance.export', [
        'from' => '2026-11-02', 'to' => '2026-11-03',
    ])))['text'];

    expect($text)->toContain('Pegawai Rentang Rekap')
        ->toContain('2026-11-02')
        ->toContain('2026-11-03')
        ->not->toContain('2026-11-04');

    $month = xlsxContents($this->actingAs($admin)->get(route('admin.daily-attendance.export', ['month' => '2026-11'])))['text'];

    expect($month)->toContain('2026-11-01')->toContain('2026-11-30');

    $this->actingAs($admin)
        ->get(route('admin.daily-attendance.export', ['from' => '2026-11-05', 'to' => '2026-11-02']))
        ->assertSessionHasErrors('to');
});

it('shows the same Hari ini, Minggu ini, and Bulan ini export options on every export page', function () {
    $admin = rangeOptionsUser(UserRole::Admin);
    $employee = rangeOptionsUser(UserRole::Employee, withEmployee: true);

    foreach ([
        [$admin, route('admin.daily-attendance')],
        [$employee, route('attendance-history.index')],
        [$employee, route('leave-requests.index')],
    ] as [$user, $url]) {
        $this->actingAs($user)->get($url)
            ->assertOk()
            ->assertSee('Hari ini')
            ->assertSee('Minggu ini')
            ->assertSee('Bulan ini')
            ->assertSee('Konfirmasi dan export');
    }

    Livewire::actingAs($admin)->test('pages::admin.kehadiran')
        ->assertSee('Hari ini')
        ->assertSee('Minggu ini')
        ->assertSee('Bulan ini')
        ->assertSee('Konfirmasi dan export');
});

it('keeps the leave request filters in every export option', function () {
    $employee = rangeOptionsUser(UserRole::Employee, withEmployee: true);

    $html = $this->actingAs($employee)
        ->get(route('leave-requests.index', ['status' => 'pending', 'type' => 'izin', 'q' => 'keluarga']))
        ->assertOk()
        ->getContent();

    preg_match_all('/href="([^"]*permohonan\/ekspor[^"]*)"/', $html, $matches);

    expect($matches[1])->not->toBeEmpty();

    foreach ($matches[1] as $href) {
        $decoded = html_entity_decode($href);

        expect($decoded)->toContain('status=pending')->toContain('type=izin')->toContain('q=keluarga');
    }
});
