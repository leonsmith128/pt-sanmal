<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_records', function (Blueprint $table): void {
            $table->string('client_code_snapshot')->nullable()->after('project_id');
            $table->string('client_name_snapshot')->nullable()->after('client_code_snapshot');
            $table->string('project_code_snapshot')->nullable()->after('client_name_snapshot');
            $table->string('project_name_snapshot')->nullable()->after('project_code_snapshot');
            $table->string('location_name_snapshot')->nullable()->after('project_name_snapshot');
            $table->string('snapshot_source', 30)->nullable()->after('location_name_snapshot');
        });

        // Data lama diisi dari relasi master yang tersedia saat migrasi.
        // Nilai ini baseline, bukan bukti nama historis yang terverifikasi.
        DB::table('attendance_records')
            ->leftJoin('projects', 'attendance_records.project_id', '=', 'projects.id')
            ->leftJoin('clients', 'projects.client_id', '=', 'clients.id')
            ->leftJoin('locations', 'attendance_records.location_id', '=', 'locations.id')
            ->select([
                'attendance_records.id as id',
                'clients.code as client_code',
                'clients.name as client_name',
                'projects.code as project_code',
                'projects.name as project_name',
                'locations.name as location_name',
            ])
            ->orderBy('attendance_records.id')
            ->chunkById(500, function ($records): void {
                foreach ($records as $record) {
                    DB::table('attendance_records')
                        ->where('id', $record->id)
                        ->update([
                            'client_code_snapshot' => $record->client_code,
                            'client_name_snapshot' => $record->client_name,
                            'project_code_snapshot' => $record->project_code,
                            'project_name_snapshot' => $record->project_name,
                            'location_name_snapshot' => $record->location_name,
                            'snapshot_source' => 'migration_backfill',
                        ]);
                }
            }, 'attendance_records.id', 'id');
    }

    public function down(): void
    {
        Schema::table('attendance_records', function (Blueprint $table): void {
            $table->dropColumn([
                'client_code_snapshot',
                'client_name_snapshot',
                'project_code_snapshot',
                'project_name_snapshot',
                'location_name_snapshot',
                'snapshot_source',
            ]);
        });
    }
};
