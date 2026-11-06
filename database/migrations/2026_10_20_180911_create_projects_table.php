<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->restrictOnDelete();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->string('project_type', 30)->default('service');
            $table->foreignId('location_id')->constrained('locations')->restrictOnDelete();
            $table->text('description')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->timestamps();

            $table->index(['client_id', 'status']);
            // Prevent a project from referencing a Site owned by another Client.
            $table->foreign(['location_id', 'client_id'], 'projects_location_client_foreign')
                ->references(['id', 'client_id'])
                ->on('locations')
                ->restrictOnDelete();
        });

        // MySQL/MariaDB enforce enum-like values at database level.
        // SQLite relies on the matching model / request validation.
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement(<<<'SQL'
                ALTER TABLE projects
                ADD CONSTRAINT projects_status_check
                CHECK (status IN ('pending', 'active', 'completed', 'cancelled'))
            SQL);

            DB::statement(<<<'SQL'
                ALTER TABLE projects
                ADD CONSTRAINT projects_project_type_check
                CHECK (project_type IN ('service', 'training'))
            SQL);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
