<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_records', function (Blueprint $table): void {
            // Legacy rows keep their original timezone interpretation. New events
            // record whether timezone came from office policy, GPS, fallback, or
            // the session anchor.
            $table->string('timezone_source', 30)->default('legacy')->after('timezone');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_records', function (Blueprint $table): void {
            $table->dropColumn('timezone_source');
        });
    }
};
