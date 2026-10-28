<?php

use App\Enums\UserRole;
use App\Http\Controllers\AdminLeaveRequestController;
use App\Http\Controllers\AdminOvertimeRequestController;
use App\Http\Controllers\AttendanceSelfieController;
use App\Http\Controllers\LeaveRequestController;
use App\Http\Controllers\OvertimeRequestController;
use App\Http\Middleware\EnsureUserHasRole;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::livewire('absensi', 'pages::attendance')->name('attendance');

    Route::get('permohonan', [LeaveRequestController::class, 'index'])->name('leave-requests.index');
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
            Route::get('kehadiran/{record}/selfie', [AttendanceSelfieController::class, 'show'])->name('attendance.selfie');

            Route::get('permohonan', [AdminLeaveRequestController::class, 'index'])->name('leave-requests.index');
            Route::get('permohonan/{leaveRequest}', [AdminLeaveRequestController::class, 'show'])->name('leave-requests.show');
            Route::get('permohonan/{leaveRequest}/lampiran', [AdminLeaveRequestController::class, 'downloadAttachment'])->name('leave-requests.attachment');
            Route::post('permohonan/{leaveRequest}/keputusan', [AdminLeaveRequestController::class, 'decide'])->name('leave-requests.decide');

            Route::get('lembur', [AdminOvertimeRequestController::class, 'index'])->name('overtime-requests.index');
            Route::get('lembur/{overtimeRequest}', [AdminOvertimeRequestController::class, 'show'])->name('overtime-requests.show');
            Route::post('lembur/{overtimeRequest}/keputusan', [AdminOvertimeRequestController::class, 'decide'])->name('overtime-requests.decide');
            Route::post('lembur/{overtimeRequest}/pengecualian-foto', [AdminOvertimeRequestController::class, 'allowClockOutWithoutPhoto'])->name('overtime-requests.photo-exception');

            Route::get('rekap-harian', [AdminLeaveRequestController::class, 'daily'])->name('daily-attendance');
            Route::post('rekap-harian/koreksi', [AdminLeaveRequestController::class, 'correctDailyStatus'])->name('daily-attendance.correct');
            Route::get('rekap-harian/export', [AdminLeaveRequestController::class, 'exportMonth'])->name('daily-attendance.export');
        });
});

Route::fallback(function () {
    return auth()->check() ? abort(404) : redirect()->route('login');
});

require __DIR__.'/settings.php';
