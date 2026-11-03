<?php

namespace App\Http\Controllers;

use App\Enums\LeaveRequestStatus;
use App\Enums\OvertimeApprovalStatus;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\OvertimeRequest;
use App\Services\DailyAttendanceStatusService;
use App\Services\EmployeeDashboardAggregationService;
use App\Support\AttendanceDateFilter;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function __invoke(
        DailyAttendanceStatusService $statusService,
        EmployeeDashboardAggregationService $employeeDashboardService,
    ): View {
        $user = auth()->user();

        if (! $user->isManager()) {
            $employee = $user->employee;

            return view('pages::dashboard', [
                'isManager' => false,
                'today' => now()->translatedFormat('l, d F Y'),
                'employeeDashboard' => $employee
                    ? $employeeDashboardService->forEmployee($employee)
                    : null,
                'notifications' => $user->notifications()->latest()->limit(5)->get(),
                'unreadNotificationCount' => $user->unreadNotifications()->count(),
            ]);
        }

        $today = now('Asia/Makassar')->toDateString();
        $employees = Employee::query()
            ->whereHas('user', fn ($query) => $query->where('is_active', true))
            ->with('user:id,name')
            ->get();

        $daily = $statusService->forEmployeesDateRange($employees, $today, $today, 'Asia/Makassar');
        $counts = collect($daily)->map(fn ($days) => $days[$today]['status'] ?? 'no_record')->countBy();

        $recentAttendanceQuery = AttendanceRecord::query()->with('employee.user');
        AttendanceDateFilter::applyInTimezone($recentAttendanceQuery, $today, $today, 'Asia/Makassar');
        $recentAttendance = $recentAttendanceQuery
            ->latest('recorded_at')
            ->limit(8)
            ->get();

        return view('pages::dashboard', [
            'isManager' => true,
            'activeEmployees' => $employees->count(),
            'presentToday' => $counts->get('present', 0),
            'incompleteToday' => $counts->get('incomplete', 0),
            'noRecordToday' => $counts->get('no_record', 0),
            'needsReviewToday' => $counts->get('needs_review', 0),
            'pendingLeave' => LeaveRequest::query()->where('status', LeaveRequestStatus::Pending)->count(),
            'pendingOvertime' => OvertimeRequest::query()->where('approval_status', OvertimeApprovalStatus::Pending)->count(),
            'recentAttendance' => $recentAttendance,
            'today' => Carbon::parse($today)->translatedFormat('l, d F Y'),
        ]);
    }
}
