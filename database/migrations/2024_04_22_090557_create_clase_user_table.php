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
        Schema::create('clase_user', function (Blueprint $table) {
            $table->foreignId('clase_id')->constrained('clases');
            $table->foreignId('user_id')->constrained('users');
            $table->timestamps();
            $table->primary(['clase_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clase_user');
    }
};
