<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            DB::table('projects')
                ->leftJoin('locations', 'locations.id', '=', 'projects.location_id')
                ->whereNull('locations.client_id')
                ->exists()
        ) {
            throw new RuntimeException(
                'Migrasi project dihentikan: ditemukan project yang belum terhubung ke Client melalui lokasi Site.'
            );
        }

        Schema::table('projects', function (Blueprint $table): void {
            $table->unsignedBigInteger('client_id')->nullable()->after('id');
            $table->string('project_type', 30)->default('calibration')->after('name');
        });

        // Existing projects inherit their Client from the Site they already use.
        DB::table('projects')->update([
            'client_id' => DB::raw('(SELECT locations.client_id FROM locations WHERE locations.id = projects.location_id)'),
        ]);


        Schema::table('projects', function (Blueprint $table): void {
            $table->unsignedBigInteger('client_id')->nullable(false)->change();
            $table->foreign('client_id')->references('id')->on('clients')->restrictOnDelete();
            $table->index(['client_id', 'status']);
        });

        Schema::table('locations', function (Blueprint $table): void {
            $table->unique(['id', 'client_id'], 'locations_id_client_id_unique');
        });

        Schema::table('projects', function (Blueprint $table): void {
            $table->foreign(['location_id', 'client_id'], 'projects_location_client_foreign')
                ->references(['id', 'client_id'])
                ->on('locations')
                ->restrictOnDelete();
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
            $table->dropForeign('projects_location_client_foreign');
            $table->dropForeign(['client_id']);
            $table->dropIndex(['client_id', 'status']);
            $table->dropColumn(['client_id', 'project_type']);
        });

        Schema::table('locations', function (Blueprint $table): void {
            $table->dropUnique('locations_id_client_id_unique');
        });
    }
};
