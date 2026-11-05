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

    /**
     * Apply the shared Admin Attendance event filters to an Eloquent query.
     *
     * Sorting, eager loading, pagination, and authorization remain the caller's responsibility.
     *
     * @param  Builder<AttendanceRecord>  $query
     * @return Builder<AttendanceRecord>
     */
    public function apply(Builder $query, string $reportingTimezone = 'Asia/Makassar'): Builder
    {
        AttendanceDateFilter::applyInTimezone(
            $query,
            $this->filters->from,
            $this->filters->to,
            $reportingTimezone,
        );

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

    /**
     * Determine whether a daily recap matches the selected filters without trimming its event set.
     *
     * @param  Collection<int, AttendanceRecord>  $events
     */
    public function matchesDay(Employee $employee, Collection $events): bool
    {
        if ($this->filters->employeeId !== null && $employee->id !== $this->filters->employeeId) {
            return false;
        }

        if ($this->hasEventConstraints()) {
            return $events->contains(fn (AttendanceRecord $event): bool => $this->matchesEvent($event, $employee));
        }

        if ($this->filters->search !== null && ! $this->matchesEmployeeSearch($employee)) {
            return $events->contains(fn (AttendanceRecord $event): bool => $this->containsText($event->notes, $this->filters->search));
        }

        return true;
    }

    private function hasEventConstraints(): bool
    {
        return $this->filters->clientId !== null
            || $this->filters->locationId !== null
            || $this->filters->projectId !== null
            || $this->filters->attendanceType !== null
            || $this->filters->workType !== null;
    }

    private function matchesEvent(AttendanceRecord $event, Employee $employee): bool
    {
        if ($this->filters->clientId !== null && $event->project?->client_id !== $this->filters->clientId) {
            return false;
        }

        if ($this->filters->locationId !== null && $event->location_id !== $this->filters->locationId) {
            return false;
        }

        if ($this->filters->projectId !== null && $event->project_id !== $this->filters->projectId) {
            return false;
        }

        if ($this->filters->attendanceType !== null && $event->type->value !== $this->filters->attendanceType) {
            return false;
        }

        if ($this->filters->workType !== null && $event->work_type->value !== $this->filters->workType) {
            return false;
        }

        if ($this->filters->search !== null
            && ! $this->matchesEmployeeSearch($employee)
            && ! $this->containsText($event->notes, $this->filters->search)) {
            return false;
        }

        return true;
    }

    private function matchesEmployeeSearch(Employee $employee): bool
    {
        if ($this->filters->search === null) {
            return true;
        }

        return $this->containsText($employee->user?->name, $this->filters->search)
            || $this->containsText($employee->employee_number, $this->filters->search);
    }

    private function containsText(?string $value, string $needle): bool
    {
        return $value !== null && mb_stripos($value, $needle) !== false;
    }
}
