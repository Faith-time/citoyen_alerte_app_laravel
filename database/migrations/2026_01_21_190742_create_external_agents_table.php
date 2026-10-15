<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('external_agents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('authority_id')->constrained('authorities')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            // Identification interne
            $table->string('employee_id', 50)->nullable();
            $table->string('department', 100)->nullable();
            $table->string('position', 100)->nullable();

            // Zones & Spécialisations
            $table->json('zones')->nullable();
            $table->json('specializations')->nullable();

            // Disponibilité
            $table->boolean('is_active')->default(true);
            $table->boolean('is_available')->default(true);

            $table->timestamps();

            $table->unique(['authority_id', 'user_id']);
            $table->index('authority_id');
            $table->index('user_id');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_agents');
    }
};
