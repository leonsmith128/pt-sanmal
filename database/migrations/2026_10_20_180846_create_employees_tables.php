<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            // Unik: satu akun hanya boleh punya satu profil pegawai.
            // restrictOnDelete: user yang punya profil tidak bisa terhapus (riwayat aman).
            $table->foreignId('user_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('title_id')->nullable()->constrained('titles')->restrictOnDelete();
            $table->string('employee_number', 50)->nullable()->unique();
            $table->string('phone', 30)->nullable();
            $table->string('employment_status', 30)->nullable(); // nilai final belum ditetapkan
            $table->date('joined_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
