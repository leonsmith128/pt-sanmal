<?php

namespace App\Data\Attendance;

use App\Models\AttendanceRecord;
use App\Models\Employee;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

final readonly class AttendanceFilterData
{
    public function __construct(
        public ?string $from = null,
        public ?string $to = null,
        public ?int $employeeId = null,
        public ?int $clientId = null,
        public ?int $locationId = null,
        public ?int $projectId = null,
        public ?string $search = null,
        public ?string $attendanceType = null,
        public ?string $workType = null,
    ) {}

    public static function fromArray(array $values): self
    {
        $from = self::date($values['from'] ?? null);
        $to = self::date($values['to'] ?? null);

        if ($from === null && $to !== null) {
            $from = $to;
        } elseif ($to === null && $from !== null) {
            $to = $from;
        } elseif ($from !== null && $to !== null && $from > $to) {
            [$from, $to] = [$to, $from];
        }

        return new self(
            from: $from,
            to: $to,
            employeeId: self::positiveInteger($values['employee_id'] ?? null),
            clientId: self::positiveInteger($values['client_id'] ?? null),
            locationId: self::positiveInteger($values['location_id'] ?? null),
            projectId: self::positiveInteger($values['project_id'] ?? null),
            search: self::text($values['search'] ?? null),
            attendanceType: self::text($values['attendance_type'] ?? null),
            workType: self::text($values['work_type'] ?? null),
        );
    }

    public function withDateRange(string $from, string $to): self
    {
        return new self($from, $to, $this->employeeId, $this->clientId, $this->locationId, $this->projectId, $this->search, $this->attendanceType, $this->workType);
    }

    public function hasEventConstraints(): bool
    {
        return $this->clientId !== null || $this->locationId !== null || $this->projectId !== null
            || $this->attendanceType !== null || $this->workType !== null;
    }

    /** @param Collection<int, AttendanceRecord> $events */
    public function matchesDay(Employee $employee, Collection $events): bool
    {
        if ($this->employeeId !== null && $employee->id !== $this->employeeId) {
            return false;
        }

        if ($this->hasEventConstraints()) {
            return $events->contains(fn (AttendanceRecord $event): bool => $this->matchesEvent($event, $employee));
        }

        if ($this->search !== null && ! $this->matchesEmployeeSearch($employee)) {
            return $events->contains(fn (AttendanceRecord $event): bool => $this->containsText($event->notes, $this->search));
        }

        return true;
    }

    private function matchesEvent(AttendanceRecord $event, Employee $employee): bool
    {
        if ($this->clientId !== null && $event->project?->client_id !== $this->clientId) return false;
        if ($this->locationId !== null && $event->location_id !== $this->locationId) return false;
        if ($this->projectId !== null && $event->project_id !== $this->projectId) return false;
        if ($this->attendanceType !== null && $event->type->value !== $this->attendanceType) return false;
        if ($this->workType !== null && $event->work_type->value !== $this->workType) return false;

        if ($this->search !== null && ! $this->matchesEmployeeSearch($employee)
            && ! $this->containsText($event->notes, $this->search)) return false;

        return true;
    }

    private function matchesEmployeeSearch(Employee $employee): bool
    {
        return $this->search === null
            || $this->containsText($employee->user?->name, $this->search)
            || $this->containsText($employee->employee_number, $this->search);
    }

    private function containsText(?string $value, string $needle): bool
    {
        return $value !== null && mb_stripos($value, $needle) !== false;
    }

    private static function date(mixed $value): ?string
    {
        return is_string($value) && Carbon::canBeCreatedFromFormat($value, 'Y-m-d') ? $value : null;
    }

    private static function positiveInteger(mixed $value): ?int
    {
        if (! is_int($value) && (! is_string($value) || ! ctype_digit($value))) return null;
        $value = (int) $value;

        return $value > 0 ? $value : null;
    }

    private static function text(mixed $value): ?string
    {
        if (! is_string($value)) return null;
        $value = trim($value);

        return $value !== '' ? $value : null;
    }
}
