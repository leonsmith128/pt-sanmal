<?php

namespace App\Data\Attendance;

use Illuminate\Support\Carbon;

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
        return new self(
            from: $from,
            to: $to,
            employeeId: $this->employeeId,
            clientId: $this->clientId,
            locationId: $this->locationId,
            projectId: $this->projectId,
            search: $this->search,
            attendanceType: $this->attendanceType,
            workType: $this->workType,
        );
    }

    private static function date(mixed $value): ?string
    {
        if (! is_string($value) || ! Carbon::canBeCreatedFromFormat($value, 'Y-m-d')) {
            return null;
        }

        return $value;
    }

    private static function positiveInteger(mixed $value): ?int
    {
        if (! is_int($value) && (! is_string($value) || ! ctype_digit($value))) {
            return null;
        }

        $integer = (int) $value;

        return $integer > 0 ? $integer : null;
    }

    private static function text(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value !== '' ? $value : null;
    }
}
