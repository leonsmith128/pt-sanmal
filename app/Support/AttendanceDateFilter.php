<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

class AttendanceDateFilter
{
    /**
     * Filter attendance events by their session's effective attendance date.
     *
     * Events without a session anchor are legacy/unlinked records, so they
     * retain their own effective attendance date as a compatibility fallback.
     */
    public static function apply(Builder $query, ?string $from, ?string $to): Builder
    {
        if ($from === null && $to === null) {
            return $query;
        }

        return $query->where(function (Builder $events) use ($from, $to): void {
            $events->whereHas('sessionAnchor', function (Builder $anchor) use ($from, $to): void {
                self::whereEffectiveDate($anchor, $from, $to);
            })->orWhere(function (Builder $legacy) use ($from, $to): void {
                $legacy->whereNull('attendance_records.session_anchor_id');

                self::whereEffectiveDate($legacy, $from, $to);
            });
        });
    }

    private static function whereEffectiveDate(Builder $query, ?string $from, ?string $to): void
    {
        $query->where(function (Builder $effective) use ($from, $to): void {
            $effective->whereHas('approvedCorrection', function (Builder $correction) use ($from, $to): void {
                self::applyDateBounds($correction, 'attendance_date', $from, $to);
            })->orWhere(function (Builder $original) use ($from, $to): void {
                $original->whereDoesntHave('approvedCorrection');

                self::applyDateBounds($original, 'attendance_date', $from, $to);
            });
        });
    }

    private static function applyDateBounds(Builder $query, string $column, ?string $from, ?string $to): void
    {
        if ($from !== null) {
            $query->whereDate($column, '>=', $from);
        }

        if ($to !== null) {
            $query->whereDate($column, '<=', $to);
        }
    }
 
    /**
     * Filter sessions by the calendar day in an explicit reporting timezone.
     * The session anchor's effective timestamp is authoritative; the event's
     * stored attendance_date remains the employee/session-local calendar date.
     */
    public static function applyInTimezone(
        Builder $query,
        ?string $from,
        ?string $to,
        string $timezone,
    ): Builder {
        if ($from === null && $to === null) {
            return $query;
        }

        $startUtc = $from !== null
            ? \\Illuminate\\Support\\Carbon::parse($from, $timezone)->startOfDay()->utc()->format('Y-m-d H:i:s')
            : null;
        $endUtc = $to !== null
            ? \\Illuminate\\Support\\Carbon::parse($to, $timezone)->endOfDay()->utc()->format('Y-m-d H:i:s')
            : null;

        return $query->where(function (Builder $events) use ($startUtc, $endUtc): void {
            $events->whereHas('sessionAnchor', function (Builder $anchor) use ($startUtc, $endUtc): void {
                self::whereEffectiveTimestamp($anchor, $startUtc, $endUtc);
            })->orWhere(function (Builder $legacy) use ($startUtc, $endUtc): void {
                $legacy->whereNull('attendance_records.session_anchor_id');
                self::whereEffectiveTimestamp($legacy, $startUtc, $endUtc);
            });
        });
    }

    private static function whereEffectiveTimestamp(Builder $query, ?string $startUtc, ?string $endUtc): void
    {
        $query->where(function (Builder $effective) use ($startUtc, $endUtc): void {
            $effective->whereHas('approvedCorrection', function (Builder $correction) use ($startUtc, $endUtc): void {
                self::applyTimestampBounds($correction, 'requested_recorded_at', $startUtc, $endUtc);
            })->orWhere(function (Builder $original) use ($startUtc, $endUtc): void {
                $original->whereDoesntHave('approvedCorrection');
                self::applyTimestampBounds($original, 'recorded_at', $startUtc, $endUtc);
            });
        });
    }

    private static function applyTimestampBounds(
        Builder $query,
        string $column,
        ?string $startUtc,
        ?string $endUtc,
    ): void {
        if ($startUtc !== null) {
            $query->where($column, '>=', $startUtc);
        }

        if ($endUtc !== null) {
            $query->where($column, '<=', $endUtc);
        }
    }

}
