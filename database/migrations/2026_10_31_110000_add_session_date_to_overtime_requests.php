<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Carbon;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('overtime_requests', function (Blueprint $table) {
            $table->date('session_date')->nullable()->after('employee_id');
            $table->index(['employee_id', 'session_date'], 'overtime_employee_session_date_index');
        });

        DB::table('overtime_requests')
            ->whereNotNull('clock_in_record_id')
            ->orderBy('id')
            ->chunkById(200, function ($requests): void {
                foreach ($requests as $request) {
                    $attendanceDate = DB::table('attendance_records')
                        ->where('id', $request->clock_in_record_id)
                        ->value('attendance_date');

                    if ($attendanceDate) {
                        DB::table('overtime_requests')
                            ->where('id', $request->id)
                            ->update(['session_date' => $attendanceDate]);
                    }
                }
            });

        DB::table('overtime_requests')
            ->whereNull('session_date')
            ->orderBy('id')
            ->chunkById(200, function ($requests): void {
                foreach ($requests as $request) {
                    $date = Carbon::parse($request->scheduled_start_at)
                        ->setTimezone($request->timezone ?: 'Asia/Makassar')
                        ->toDateString();

                    DB::table('overtime_requests')
                        ->where('id', $request->id)
                        ->update(['session_date' => $date]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('overtime_requests', function (Blueprint $table) {
            $table->dropIndex('overtime_employee_session_date_index');
            $table->dropColumn('session_date');
        });
    }
};
