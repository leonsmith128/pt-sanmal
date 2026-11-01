<?php

namespace App\Support;

use App\Enums\AttendanceType;
use App\Models\AttendanceRecord;
use Illuminate\Support\Carbon;

class AttendanceSessionTimeline
{
    /**
     * Ensure the corrected Clock In stays between the previous session's
     * Clock Out and this session's Clock Out / next session's Clock In.
     */
    public function assertClockIn(AttendanceRecord $anchor, Carbon $correctedAt): void
    {
        $sessions = $this->sessionsFor($anchor);
        $nextSession = null;

        foreach ($sessions as $session) {
            if ($session['id'] === $anchor->id) {
                continue;
            }

            if ($session['start']->lt($correctedAt)) {
                abort_if(
                    $session['clock_out'] === null,
                    422,
                    'Sesi sebelumnya belum memiliki Clock Out sehingga urutan waktu tidak dapat diverifikasi.'
                );
                abort_if(
                    $session['clock_out']->gte($correctedAt),
                    422,
                    'Clock In harus terjadi setelah Clock Out sesi sebelumnya.'
                );
            } elseif ($nextSession === null || $session['start']->lt($nextSession['start'])) {
                $nextSession = $session;
            }
        }

        $ownClockOut = $this->firstClockOut($anchor);
        abort_if(
            $ownClockOut && $correctedAt->gte($ownClockOut),
            422,
            'Waktu Clock In harus lebih awal daripada Clock Out pada sesi yang sama.'
        );

        abort_if(
            $nextSession && $ownClockOut === null,
            422,
            'Sesi ini belum memiliki Clock Out sehingga tidak boleh mendahului sesi berikutnya.'
        );

        abort_if(
            $nextSession && $ownClockOut && $ownClockOut->gte($nextSession['start']),
            422,
            'Clock Out sesi ini harus terjadi sebelum Clock In sesi berikutnya.'
        );
    }

    /**
     * Ensure a corrected Clock Out follows its Clock In and precedes the next session.
     */
    public function assertClockOut(AttendanceRecord $anchor, Carbon $correctedAt): void
    {
        $anchorStart = $this->effectiveTimestamp($anchor);

        abort_if(
            $correctedAt->lte($anchorStart),
            422,
            'Waktu Clock Out harus lebih akhir daripada Clock In pada sesi yang sama.'
        );

        $sessions = collect($this->sessionsFor($anchor));
        $previousSession = $sessions
            ->filter(fn (array $session): bool => $session['id'] !== $anchor->id
                && $session['start']->lt($anchorStart))
            ->sortByDesc(fn (array $session): int => $session['start']->getTimestamp())
            ->first();

        abort_if(
            $previousSession && $previousSession['clock_out'] === null,
            422,
            'Sesi sebelumnya belum memiliki Clock Out sehingga urutan waktu tidak dapat diverifikasi.'
        );
        abort_if(
            $previousSession && $previousSession['clock_out']->gte($anchorStart),
            422,
            'Clock In harus terjadi setelah Clock Out sesi sebelumnya.'
        );

        $nextSession = $sessions
            ->filter(fn (array $session): bool => $session['id'] !== $anchor->id
                && $session['start']->gt($anchorStart))
            ->sortBy(fn (array $session): int => $session['start']->getTimestamp())
            ->first();

        abort_if(
            $nextSession && $correctedAt->gte($nextSession['start']),
            422,
            'Clock Out harus terjadi sebelum Clock In sesi berikutnya.'
        );
    }

    /**
     * @return array<int, array{id: int, start: Carbon, clock_out: ?Carbon}>
     */
    private function sessionsFor(AttendanceRecord $anchor): array
    {
        return AttendanceRecord::query()
            ->where('employee_id', $anchor->employee_id)
            ->where('type', AttendanceType::ClockIn)
            ->whereColumn('session_anchor_id', 'id')
            ->with(['approvedCorrection', 'sessionEvents' => fn ($query) => $query
                ->where('type', AttendanceType::ClockOut)
                ->with('approvedCorrection')])
            ->get()
            ->map(function (AttendanceRecord $session): array {
                $clockOutEvent = $session->sessionEvents
                    ->sortBy(fn (AttendanceRecord $event): int => $this->effectiveTimestamp($event)->getTimestamp())
                    ->first();

                return [
                    'id' => $session->id,
                    'start' => $this->effectiveTimestamp($session),
                    'clock_out' => $clockOutEvent ? $this->effectiveTimestamp($clockOutEvent) : null,
                ];
            })
            ->all();
    }

    private function firstClockOut(AttendanceRecord $anchor): ?Carbon
    {
        $event = $anchor->sessionEvents()
            ->where('type', AttendanceType::ClockOut)
            ->with('approvedCorrection')
            ->get()
            ->sortBy(fn (AttendanceRecord $record): int => $this->effectiveTimestamp($record)->getTimestamp())
            ->first();

        return $event ? $this->effectiveTimestamp($event) : null;
    }

    private function effectiveTimestamp(AttendanceRecord $record): Carbon
    {
        /** @var Carbon $timestamp */
        $timestamp = $record->effectiveRecordedAt();

        return $timestamp;
    }
}
