<?php

namespace App\Services;

use Aldesrahim\TimezoneIndonesia\TimezoneIndonesia;
use App\Enums\WorkType;
use App\Support\AttendanceLocation;
use DateTimeZone;
use Illuminate\Support\Facades\Log;
use Throwable;

final class AttendanceTimezoneResolver
{
    public const COMPANY_TIMEZONE = 'Asia/Makassar';

    public function __construct(
        private readonly TimezoneIndonesia $indonesiaTimezone,
    ) {}

    /**
     * Resolve the timezone for a new attendance session.
     *
     * @return array{timezone: string, source: 'office_fixed'|'gps_resolved'|'fallback'}
     */
    public function resolveClockIn(
        WorkType $workType,
        ?float $latitude,
        ?float $longitude,
        ?float $accuracy,
    ): array {
        if ($workType === WorkType::Office) {
            return [
                'timezone' => self::COMPANY_TIMEZONE,
                'source' => 'office_fixed',
            ];
        }

        if (
            $latitude === null
            || $longitude === null
            || $accuracy === null
            || $accuracy > AttendanceLocation::MAX_ACCURACY_METERS
        ) {
            return $this->fallback();
        }

        try {
            $timezone = $this->indonesiaTimezone->at($latitude, $longitude);

            if (
                ! is_string($timezone)
                || ! in_array($timezone, DateTimeZone::listIdentifiers(DateTimeZone::ALL_WITH_BC), true)
            ) {
                return $this->fallback();
            }

            return [
                'timezone' => $timezone,
                'source' => 'gps_resolved',
            ];
        } catch (Throwable $exception) {
            // Do not log coordinates. A resolver failure must not block a valid
            // photo-backed attendance event; the fallback is flagged for review.
            Log::warning('Attendance timezone resolution failed; using the unverified company timezone.', [
                'exception' => $exception::class,
            ]);

            return $this->fallback();
        }
    }

    /**
     * @return array{timezone: string, source: 'fallback'}
     */
    private function fallback(): array
    {
        return [
            'timezone' => self::COMPANY_TIMEZONE,
            'source' => 'fallback',
        ];
    }
}
