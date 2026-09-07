<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('department_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_user_id')
                ->constrained('tenant_users')
                ->cascadeOnDelete();
            $table->foreignId('department_id')
                ->constrained('departments')
                ->cascadeOnDelete();
            $table->timestamps();
            $table->unique([
                'tenant_user_id',
                'department_id',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('department_users');
    }
};
