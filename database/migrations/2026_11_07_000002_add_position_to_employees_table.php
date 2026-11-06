<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('position')->nullable()->after('title_id');
        });

        $employees = DB::table('employees')
            ->join('users', 'users.id', '=', 'employees.user_id')
            ->leftJoin('titles', 'titles.id', '=', 'employees.title_id')
            ->whereNotNull('titles.name')
            ->select([
                'employees.id',
                'users.role',
                'titles.name as title_name',
            ])
            ->get();

        foreach ($employees as $employee) {
            $position = match ($employee->role) {
                'employee' => match ($employee->title_name) {
                    'Teknisi', 'Teknisi Kalibrasi', 'Teknisi Lapangan' => 'technician',
                    'Koordinator Proyek' => 'project_coordinator',
                    default => $employee->title_name,
                },
                'admin' => match ($employee->title_name) {
                    'Staf Administrasi' => 'staff_administration',
                    'Supervisor', 'Supervisor Operasional' => 'supervisor',
                    default => $employee->title_name,
                },
                default => $employee->title_name,
            };

            DB::table('employees')
                ->where('id', $employee->id)
                ->update(['position' => $position]);
        }
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('position');
        });
    }
};
