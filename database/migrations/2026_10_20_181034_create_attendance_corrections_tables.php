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
            // Nullable: untuk kasus lupa clock-out (belum ada record yang dikoreksi)
            $table->foreignId('attendance_record_id')->nullable()->constrained('attendance_records')->restrictOnDelete();

            $table->string('requested_type', 20);          // clock_in / clock_out
            $table->timestamp('requested_recorded_at');    // UTC
            $table->date('attendance_date');
            $table->text('reason');
            $table->string('attachment_path')->nullable();

            $table->string('status', 20)->default('pending')->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_corrections');
    }
};