<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('overtime_requests', function (Blueprint $table) {
            $table->string('reason', 2000)->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('overtime_requests')->whereNull('reason')->update(['reason' => '']);

        Schema::table('overtime_requests', function (Blueprint $table) {
            $table->string('reason', 2000)->nullable(false)->change();
        });
    }
};
