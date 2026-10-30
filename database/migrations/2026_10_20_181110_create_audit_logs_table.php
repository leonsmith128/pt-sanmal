<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // pelaku
            $table->string('action', 100)->index();   // mis. leave.approved, account.deactivated
            $table->nullableMorphs('auditable');      // objek yang terkena (otomatis ber-index)
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent(); // log tidak diubah, jadi tanpa updated_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};