<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_records', function (Blueprint $table) {
            $table->foreignId('session_anchor_id')->nullable()->after('employee_id')
                ->constrained('attendance_records')->nullOnDelete();
            $table->string('source', 30)->default('attendance')->after('session_anchor_id');
            $table->foreignId('attendance_correction_id')->nullable()->after('source')
                ->constrained('attendance_corrections')->nullOnDelete();
            $table->index(['employee_id', 'session_anchor_id', 'type'], 'attendance_session_events_idx');
        });

        Schema::table('attendance_corrections', function (Blueprint $table) {
            $table->foreignId('session_anchor_id')->nullable()->after('attendance_record_id')
                ->constrained('attendance_records')->restrictOnDelete();
            $table->index(['employee_id', 'session_anchor_id', 'status'], 'attendance_correction_session_idx');
        });

        // Backfill hanya rangkaian lama yang dapat dipasangkan secara deterministik.
        // Clock In menjadi anchor dirinya sendiri; Clock Out dipasangkan ke Clock In
        // terbuka berikutnya secara kronologis. Rangkaian ambigu dibiarkan NULL untuk review.
        DB::table('attendance_records')
            ->where('type', 'overtime_start')
            ->whereNull('session_anchor_id')
            ->orderBy('id')
            ->get()
            ->each(function ($event): void {
                $clockInId = DB::table('overtime_requests')
                    ->where('start_record_id', $event->id)
                    ->value('clock_in_record_id');

                if ($clockInId) {
                    DB::table('attendance_records')->where('id', $event->id)
                        ->update(['session_anchor_id' => $clockInId]);
                }
            });

        $employeeIds = DB::table('attendance_records')->distinct()->pluck('employee_id');
        foreach ($employeeIds as $employeeId) {
            $activeAnchor = null;
            $ambiguous = false;

            $events = DB::table('attendance_records')
                ->where('employee_id', $employeeId)
                ->whereIn('type', ['clock_in', 'clock_out'])
                ->orderBy('recorded_at')
                ->orderBy('id')
                ->get();

            foreach ($events as $event) {
                if ($event->type === 'clock_in') {
                    if ($activeAnchor !== null) {
                        $ambiguous = true;
                        $activeAnchor = null;
                    }

                    DB::table('attendance_records')->where('id', $event->id)
                        ->update(['session_anchor_id' => $event->id]);
                    $activeAnchor = $event->id;
                    $ambiguous = false;
                    continue;
                }

                if ($event->type === 'clock_out' && $activeAnchor !== null && ! $ambiguous) {
                    DB::table('attendance_records')->where('id', $event->id)
                        ->update(['session_anchor_id' => $activeAnchor]);
                    $activeAnchor = null;
                }
            }
        }

        DB::table('attendance_corrections')
            ->whereNotNull('attendance_record_id')
            ->whereNull('session_anchor_id')
            ->orderBy('id')
            ->get()
            ->each(function ($correction): void {
                $record = DB::table('attendance_records')->where('id', $correction->attendance_record_id)->first();
                if ($record) {
                    DB::table('attendance_corrections')->where('id', $correction->id)
                        ->update(['session_anchor_id' => $record->session_anchor_id ?? ($record->type === 'clock_in' ? $record->id : null)]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('attendance_corrections', function (Blueprint $table) {
            $table->dropIndex('attendance_correction_session_idx');
            $table->dropConstrainedForeignId('session_anchor_id');
        });

        Schema::table('attendance_records', function (Blueprint $table) {
            $table->dropIndex('attendance_session_events_idx');
            $table->dropConstrainedForeignId('attendance_correction_id');
            $table->dropColumn('source');
            $table->dropConstrainedForeignId('session_anchor_id');
        });
    }
};
