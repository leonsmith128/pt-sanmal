<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_records', function (Blueprint $table) {
            $table->string('drive_backup_status', 20)->default('pending');
            $table->string('drive_file_id')->nullable();
            $table->char('drive_md5_checksum', 32)->nullable();
            $table->unsignedBigInteger('drive_size_bytes')->nullable();
            $table->timestamp('drive_backed_up_at')->nullable();
            $table->timestamp('local_deleted_at')->nullable();
            $table->index('drive_backup_status');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_records', function (Blueprint $table) {
            $table->dropIndex(['drive_backup_status']);
            $table->dropColumn([
                'drive_backup_status',
                'drive_file_id',
                'drive_md5_checksum',
                'drive_size_bytes',
                'drive_backed_up_at',
                'local_deleted_at',
            ]);
        });
    }
};
