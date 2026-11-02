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
            $table->foreignId('session_anchor_id')->nullable()
                ->constrained('attendance_records')->restrictOnDelete();
            $table->string('source', 30)->default('attendance');
            // Foreign key dipasang setelah attendance_corrections dibuat untuk menghindari
            // dependensi melingkar antara attendance_records dan attendance_corrections.
            $table->unsignedBigInteger('attendance_correction_id')->nullable();

            $table->string('type', 20);
            $table->string('work_type', 20);
            $table->foreignId('location_id')->nullable()->constrained('locations')->restrictOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('projects')->restrictOnDelete();

            $table->timestamp('recorded_at'); // UTC, ditentukan server
            $table->timestamp('captured_at')->nullable(); // waktu perangkat saat bukti diambil
            $table->string('timezone', 50);
            $table->date('attendance_date');

            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('accuracy_m', 8, 2)->nullable();
            $table->string('location_status', 20)->default('captured');
            $table->string('address_snapshot', 500)->nullable();
            $table->string('selfie_path')->nullable();
            $table->string('selfie_source', 10)->nullable();

            $table->string('drive_backup_status', 20)->default('pending');
            $table->string('drive_file_id')->nullable();
            $table->char('drive_md5_checksum', 32)->nullable();
            $table->unsignedBigInteger('drive_size_bytes')->nullable();
            $table->timestamp('drive_backed_up_at')->nullable();
            $table->timestamp('local_deleted_at')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'recorded_at']);
            $table->index(['employee_id', 'attendance_date']);
            $table->index(['employee_id', 'type', 'recorded_at']);
            $table->index('attendance_date');
            $table->index('location_status');
            $table->index('drive_backup_status');
            $table->index(['employee_id', 'session_anchor_id', 'type'], 'attendance_session_events_idx');
            $table->index('attendance_correction_id', 'attendance_correction_event_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
    }
};
