<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $highestNumber = 0;

        foreach (DB::table('employees')->pluck('employee_number') as $employeeNumber) {
            if (is_string($employeeNumber) && preg_match('/\AEMP-(\d+)\z/', $employeeNumber, $matches) === 1) {
                $highestNumber = max($highestNumber, (int) $matches[1]);
            }
        }

        Schema::create('employee_number_sequences', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
            $table->unsignedBigInteger('next_number');
            $table->timestamps();
        });

        DB::table('employee_number_sequences')->insert([
            'id' => 1,
            'next_number' => $highestNumber + 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_number_sequences');
    }
};
