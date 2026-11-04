<?php

namespace App\Filters;

use App\Data\Attendance\AttendanceFilterData;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Support\AttendanceDateFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final readonly class AttendanceRecordFilter
{
    public function __construct(private AttendanceFilterData $filters) {}

    /** @param Builder<AttendanceRecord> $query
     *  @return Builder<AttendanceRecord>
     */
    public function apply(Builder $query, string $reportingTimezone = 'Asia/Makassar'): Builder
    {
        AttendanceDateFilter::applyInTimezone($query, $this->filters->from, $this->filters->to, $reportingTimezone);

        if ($this->filters->employeeId !== null) {
            $query->where('attendance_records.employee_id', $this->filters->employeeId);
        }
        if ($this->filters->clientId !== null) {
            $query->whereHas('project', fn (Builder $project) => $project->where('client_id', $this->filters->clientId));
        }
        if ($this->filters->locationId !== null) {
            // Historical event Location; a Project's current Location may have changed.
            $query->where('attendance_records.location_id', $this->filters->locationId);
        }
        if ($this->filters->projectId !== null) {
            $query->where('attendance_records.project_id', $this->filters->projectId);
        }
        if ($this->filters->attendanceType !== null) {
            $query->where('attendance_records.type', $this->filters->attendanceType);
        }
        if ($this->filters->workType !== null) {
            $query->where('attendance_records.work_type', $this->filters->workType);
        }
        if ($this->filters->search !== null) {
            $term = $this->filters->search;
            $query->where(function (Builder $nested) use ($term): void {
                $nested->whereHas('employee.user', fn (Builder $user) => $user->where('name', 'like', '%'.$term.'%'))
                    ->orWhereHas('employee', fn (Builder $employee) => $employee->where('employee_number', 'like', '%'.$term.'%'))
                    ->orWhere('attendance_records.notes', 'like', '%'.$term.'%');
            });
        }

        return $query;
    }

    /** @param Collection<int, AttendanceRecord> $events */
    public function matchesDay(Employee $employee, Collection $events): bool
    {
        return $this->filters->matchesDay($employee, $events);
    }
}
