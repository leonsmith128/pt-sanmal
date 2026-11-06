<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->nullable()->constrained('clients')->restrictOnDelete();
            $table->string('name');
            $table->string('type', 20)->default('site');
            $table->text('address')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('timezone', 50)->default('Asia/Makassar');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['client_id', 'is_active']);
            // Target composite foreign key projects(location_id, client_id).
            $table->unique(['id', 'client_id'], 'locations_id_client_id_unique');
        });

        // MySQL/MariaDB enforce this invariant at database level.
        // SQLite relies on the matching Location model validation.
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement(<<<'SQL'
                ALTER TABLE locations
                ADD CONSTRAINT locations_type_client_consistency_check
                CHECK (
                    (type = 'office' AND client_id IS NULL)
                    OR (type = 'site' AND client_id IS NOT NULL)
                )
            SQL);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
