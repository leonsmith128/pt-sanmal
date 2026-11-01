<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_corrections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            // Nullable untuk kasus lupa clock-out (belum ada record yang dikoreksi).
            $table->foreignId('attendance_record_id')->nullable()->constrained('attendance_records')->restrictOnDelete();
            $table->foreignId('session_anchor_id')->nullable()
                ->constrained('attendance_records')->restrictOnDelete();

            $table->string('requested_type', 20);
            $table->timestamp('requested_recorded_at');
            $table->date('attendance_date');
            $table->text('reason');
            $table->string('attachment_path')->nullable();

            $table->string('status', 20)->default('pending')->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('decision_seen_at')->nullable();
            $table->text('review_note')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'session_anchor_id', 'status'], 'attendance_correction_session_idx');
        });

        // Relasi balik dibuat setelah kedua tabel tersedia.
        Schema::table('attendance_records', function (Blueprint $table) {
            $table->foreign('attendance_correction_id', 'attendance_records_correction_fk')
                ->references('id')->on('attendance_corrections')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('attendance_records', function (Blueprint $table) {
            $table->dropForeign('attendance_records_correction_fk');
        });

        Schema::dropIfExists('attendance_corrections');
    }
};
