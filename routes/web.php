<?php

use App\Enums\UserRole;
use App\Http\Controllers\AdminAttendanceCorrectionController;
use App\Http\Controllers\AdminAuditLogController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminDailyAttendanceController;
use App\Http\Controllers\AdminAttendanceExportController;
use App\Http\Controllers\AdminEmployeeController;
use App\Http\Controllers\AdminLeaveRequestController;
use App\Http\Controllers\AdminOvertimeRequestController;
use App\Http\Controllers\AttendanceHistoryController;
use App\Http\Controllers\AttendanceSelfieController;
use App\Http\Controllers\LeaveRequestController;
use App\Http\Controllers\OvertimeRequestController;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\EnsureUserHasRole;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login')->name('home');

Route::middleware(['auth', EnsureUserIsActive::class, 'verified'])->group(function () {
    Route::get('dashboard', AdminDashboardController::class)->name('dashboard');
    Route::livewire('profil', 'pages::employee-profile')->name('profile.edit');
    Route::redirect('profil-pegawai', '/profil')->name('employee-profile.edit');
    Route::livewire('absensi', 'pages::attendance')->name('attendance');
    Route::get('riwayat-absensi', [AttendanceHistoryController::class, 'index'])->name('attendance-history.index');
    Route::get('riwayat-absensi/ekspor', [AttendanceHistoryController::class, 'export'])->name('attendance-history.export');
    Route::get('riwayat-absensi/{record}/foto', [AttendanceSelfieController::class, 'showForEmployee'])->name('attendance-history.selfie');

    Route::get('permohonan', [LeaveRequestController::class, 'index'])->name('leave-requests.index');
    Route::get('permohonan/ekspor', [LeaveRequestController::class, 'export'])->name('leave-requests.export');
    Route::get('permohonan/buat', [LeaveRequestController::class, 'create'])->name('leave-requests.create');
    Route::post('permohonan', [LeaveRequestController::class, 'store'])->name('leave-requests.store');
    Route::patch('permohonan/{leaveRequest}/batalkan', [LeaveRequestController::class, 'cancel'])->name('leave-requests.cancel');

    Route::get('lembur', [OvertimeRequestController::class, 'index'])->name('overtime-requests.index');
    Route::post('lembur', [OvertimeRequestController::class, 'store'])->name('overtime-requests.store');
    Route::patch('lembur/{overtimeRequest}/batalkan', [OvertimeRequestController::class, 'cancel'])->name('overtime-requests.cancel');

    // Halaman pengelolaan: admin dan superadmin saja.
    Route::middleware(EnsureUserHasRole::using(UserRole::Admin, UserRole::Superadmin))
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {

            Route::livewire('kehadiran', 'pages::admin.kehadiran')->name('kehadiran');
            Route::get('kehadiran/ekspor', [AdminAttendanceExportController::class, 'export'])->name('kehadiran.export');
            Route::get('kehadiran/{record}/selfie', [AttendanceSelfieController::class, 'show'])->name('attendance.selfie');

            Route::resource('pegawai', AdminEmployeeController::class)
                ->parameters(['pegawai' => 'employee'])
                ->names('employees')
                ->except(['destroy']);

            Route::get('koreksi-absensi', [AdminAttendanceCorrectionController::class, 'index'])
                ->name('attendance-corrections.index');
            Route::get('koreksi-absensi/{attendanceCorrection}', [AdminAttendanceCorrectionController::class, 'show'])
                ->name('attendance-corrections.show');
            Route::get('koreksi-absensi/{attendanceCorrection}/lampiran', [AdminAttendanceCorrectionController::class, 'attachment'])
                ->name('attendance-corrections.attachment');
            Route::post('koreksi-absensi/{attendanceCorrection}/keputusan', [AdminAttendanceCorrectionController::class, 'decide'])
                ->name('attendance-corrections.decide');

            Route::get('audit-log', [AdminAuditLogController::class, 'index'])->name('audit-logs.index');

            Route::get('permohonan', [AdminLeaveRequestController::class, 'index'])->name('leave-requests.index');
            Route::get('permohonan/{leaveRequest}', [AdminLeaveRequestController::class, 'show'])->name('leave-requests.show');
            Route::get('permohonan/{leaveRequest}/lampiran', [AdminLeaveRequestController::class, 'downloadAttachment'])->name('leave-requests.attachment');
            Route::post('permohonan/{leaveRequest}/keputusan', [AdminLeaveRequestController::class, 'decide'])->name('leave-requests.decide');

            Route::get('lembur', [AdminOvertimeRequestController::class, 'index'])->name('overtime-requests.index');
            Route::get('lembur/{overtimeRequest}', [AdminOvertimeRequestController::class, 'show'])->name('overtime-requests.show');
            Route::post('lembur/{overtimeRequest}/keputusan', [AdminOvertimeRequestController::class, 'decide'])->name('overtime-requests.decide');
            Route::post('lembur/{overtimeRequest}/pengecualian-foto', [AdminOvertimeRequestController::class, 'allowClockOutWithoutPhoto'])->name('overtime-requests.photo-exception');

            Route::get('rekap-harian', [AdminDailyAttendanceController::class, 'daily'])->name('daily-attendance');
            Route::post('rekap-harian/koreksi', [AdminDailyAttendanceController::class, 'correctDailyStatus'])->name('daily-attendance.correct');
            Route::get('rekap-harian/export', [AdminDailyAttendanceController::class, 'exportMonth'])->name('daily-attendance.export');
        });
});

Route::fallback(function () {
    return auth()->check() ? abort(404) : redirect()->route('login');
});

require __DIR__.'/settings.php';
