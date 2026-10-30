<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('overtime_requests', function (Blueprint $table) {
            $table->id();
            // Relasi utama
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            $table->foreignId('clock_in_record_id')->constrained('attendance_records')->restrictOnDelete();
            $table->foreignId('start_record_id')->nullable()->unique()->constrained('attendance_records')->restrictOnDelete();
            $table->foreignId('clock_out_record_id')->nullable()->unique()->constrained('attendance_records')->restrictOnDelete();
            // Informasi pengajuan lembur
            $table->string('reason', 2000);
            $table->timestamp('scheduled_start_at'); // UTC, waktu rencana
            $table->string('timezone', 50)->default('Asia/Makassar');
            // Status lembur
            $table->string('state', 30)->default('scheduled');
            $table->string('approval_status', 20)->default('pending');
            // Informasi konfirmasi dan notifikasi
            $table->timestamp('photo_confirmed_at')->nullable(); // Waktu server, bukan waktu browser
            $table->timestamp('reminder_sent_at')->nullable();
            $table->timestamp('start_due_notified_at')->nullable();
            // Informasi penyelesaian dan pembatalan
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            // Informasi keputusan persetujuan
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->timestamp('decision_seen_at')->nullable();
            $table->text('decision_note')->nullable();
            // Pengecualian konfirmasi foto
            $table->text('photo_exception_reason')->nullable();
            $table->timestamps();
            // Index
            $table->index(['employee_id', 'scheduled_start_at']);
            $table->index(['state', 'scheduled_start_at']);
            $table->index(['approval_status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('overtime_requests');
    }
};
