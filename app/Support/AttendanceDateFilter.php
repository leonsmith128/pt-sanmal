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
}
