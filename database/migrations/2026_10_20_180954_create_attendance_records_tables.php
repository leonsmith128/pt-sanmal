<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Satu baris = satu event absensi: clock_in, overtime_start, atau clock_out.
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            $table->string('type', 20);
            $table->string('work_type', 20);
            $table->foreignId('location_id')->nullable()->constrained('locations')->restrictOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('projects')->restrictOnDelete();

            $table->timestamp('recorded_at'); // UTC, ditentukan server
            $table->string('timezone', 50);
            $table->date('attendance_date');

            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('accuracy_m', 8, 2)->nullable();
            $table->string('address_snapshot', 500)->nullable();
            $table->string('selfie_path')->nullable();
            $table->string('selfie_source', 10)->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'recorded_at']);
            $table->index(['employee_id', 'attendance_date']);
            $table->index(['employee_id', 'type', 'recorded_at']);
            $table->index('attendance_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
    }
};
