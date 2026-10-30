<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_requests', function (Blueprint $table): void {
            $table->timestamp('decision_seen_at')->nullable()->after('decided_at');
        });

        Schema::table('overtime_requests', function (Blueprint $table): void {
            $table->timestamp('decision_seen_at')->nullable()->after('decided_at');
        });

        Schema::table('attendance_corrections', function (Blueprint $table): void {
            $table->timestamp('decision_seen_at')->nullable()->after('reviewed_at');
        });
    }

    public function down(): void
    {
        Schema::table('leave_requests', fn (Blueprint $table) => $table->dropColumn('decision_seen_at'));
        Schema::table('overtime_requests', fn (Blueprint $table) => $table->dropColumn('decision_seen_at'));
        Schema::table('attendance_corrections', fn (Blueprint $table) => $table->dropColumn('decision_seen_at'));
    }
};
