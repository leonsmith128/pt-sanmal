<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('overtime_requests', function (Blueprint $table) {
            $table->timestamp('scheduled_end_at')->nullable()->after('scheduled_start_at');
            $table->string('finalization_source', 20)->nullable()->after('photo_exception_reason');
            $table->timestamp('final_start_at')->nullable()->after('finalization_source');
            $table->timestamp('final_end_at')->nullable()->after('final_start_at');
            $table->text('finalization_reason')->nullable()->after('final_end_at');
            $table->foreignId('finalized_by')->nullable()->after('finalization_reason')->constrained('users')->nullOnDelete();
            $table->timestamp('finalized_at')->nullable()->after('finalized_by');
        });

        Schema::table('overtime_requests', function (Blueprint $table) {
            $table->dropForeign(['clock_in_record_id']);
            $table->foreignId('clock_in_record_id')->nullable()->change();
            $table->foreign('clock_in_record_id')->references('id')->on('attendance_records')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('overtime_requests', function (Blueprint $table) {
            $table->dropForeign(['clock_in_record_id']);
            $table->foreignId('clock_in_record_id')->nullable(false)->change();
            $table->foreign('clock_in_record_id')->references('id')->on('attendance_records')->restrictOnDelete();
        });

        Schema::table('overtime_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('finalized_by');
            $table->dropColumn([
                'scheduled_end_at', 'finalization_source', 'final_start_at',
                'final_end_at', 'finalization_reason', 'finalized_at',
            ]);
        });
    }
};
