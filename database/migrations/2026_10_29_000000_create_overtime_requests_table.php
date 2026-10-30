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
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            $table->foreignId('clock_in_record_id')->constrained('attendance_records')->restrictOnDelete();
            $table->foreignId('start_record_id')->nullable()->unique()->constrained('attendance_records')->restrictOnDelete();
            $table->foreignId('clock_out_record_id')->nullable()->unique()->constrained('attendance_records')->restrictOnDelete();

            $table->string('reason', 2000);
            $table->timestamp('scheduled_start_at');
            $table->string('timezone', 50)->default('Asia/Makassar');
            $table->string('state', 30)->default('scheduled');
            $table->string('approval_status', 20)->default('pending');
            $table->timestamp('photo_confirmed_at')->nullable();
            $table->timestamp('scheduled_start_at'); // UTC, waktu rencana
            $table->string('timezone', 50)->default('Asia/Makassar');
            $table->string('state', 30)->default('scheduled');
            $table->string('approval_status', 20)->default('pending');
            $table->timestamp('photo_confirmed_at')->nullable(); // waktu server, bukan waktu browser
            $table->timestamp('reminder_sent_at')->nullable();
            $table->timestamp('start_due_notified_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->timestamp('decision_seen_at')->nullable();
            $table->text('decision_note')->nullable();
            $table->text('photo_exception_reason')->nullable();
            $table->timestamps();

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
