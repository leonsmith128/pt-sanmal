<?php

namespace App\Http\Controllers;

use App\Data\Attendance\AttendanceFilterData;
use App\Enums\AttendanceType;
use App\Enums\WorkType;
use App\Models\Employee;
use App\Services\AttendanceReportBuilder;
use App\Support\ReportFileName;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AdminAttendanceExportController extends Controller
{
    public function export(Request $request, AttendanceReportBuilder $builder): BinaryFileResponse
    {
        $filters = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
            'employee_id' => ['nullable', 'integer', 'exists:employees,id'],
            'client_id' => ['nullable', 'integer', 'exists:clients,id'],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'search' => ['nullable', 'string', 'max:255'],
            'attendance_type' => ['nullable', Rule::enum(AttendanceType::class)],
            'work_type' => ['nullable', Rule::enum(WorkType::class)],
        ]);

        $today = Carbon::now('Asia/Makassar')->startOfDay();
        $filterData = AttendanceFilterData::fromArray($filters);

        // Tanpa filter tanggal: bulan berjalan (bukan seluruh histori).
        if ($filterData->from === null && $filterData->to === null) {
            $filterData = $filterData->withDateRange($today->copy()->startOfMonth()->toDateString(), $today->toDateString());
        }

        $from = $filterData->from ?? $today->toDateString();
        $to = $filterData->to ?? $from;
        $start = Carbon::parse($from, 'Asia/Makassar')->startOfDay();
        $end = Carbon::parse($to, 'Asia/Makassar')->startOfDay();

        $response = Excel::download(
            $builder->build(Employee::query(), $start, $end, 'Asia/Makassar', $filterData),
            ReportFileName::admin('kehadiran', ReportFileName::period($start, $end)),
        );
        $response->headers->set('Cache-Control', 'private, no-store, no-cache, must-revalidate');

        return $response;
    }
}
