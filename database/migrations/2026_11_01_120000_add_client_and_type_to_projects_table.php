<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            $table->unsignedBigInteger('client_id')->nullable()->after('id');
            $table->string('project_type', 30)->default('calibration')->after('name');
        });

        // Existing projects inherit their Client from the Site they already use.
        DB::table('projects')->update([
            'client_id' => DB::raw('(SELECT locations.client_id FROM locations WHERE locations.id = projects.location_id)'),
        ]);

        if (DB::table('projects')->whereNull('client_id')->exists()) {
            throw new RuntimeException(
                'Migrasi project dihentikan: ditemukan project yang belum terhubung ke Client melalui lokasi Site.'
            );
        }

        Schema::table('projects', function (Blueprint $table): void {
            $table->unsignedBigInteger('client_id')->nullable(false)->change();
            $table->foreign('client_id')->references('id')->on('clients')->restrictOnDelete();
            $table->index(['client_id', 'status']);
        });

        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement(<<<'SQL'
                ALTER TABLE projects
                ADD CONSTRAINT projects_project_type_check
                CHECK (project_type IN ('calibration', 'training'))
            SQL);
        }
    }

    public function down(): void
    {
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE projects DROP CONSTRAINT projects_project_type_check');
        }

        Schema::table('projects', function (Blueprint $table): void {
            $table->dropForeign(['client_id']);
            $table->dropIndex(['client_id', 'status']);
            $table->dropColumn(['client_id', 'project_type']);
        });
    }
};
