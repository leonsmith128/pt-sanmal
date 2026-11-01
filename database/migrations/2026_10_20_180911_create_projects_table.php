<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\DB;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->foreignId('location_id')->constrained('locations')->restrictOnDelete();
            $table->text('description')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->timestamps();
        });

        // SQLite tidak mendukung ALTER TABLE ... ADD CONSTRAINT.
        if (DB::getDriverName() === 'mysql') {
            DB::statement(<<<'SQL'
                ALTER TABLE projects
                ADD CONSTRAINT projects_status_check
                CHECK (status IN ('pending', 'active', 'completed', 'cancelled'))
            SQL);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
