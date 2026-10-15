<?php
// database/migrations/2024_01_01_000002_create_communes_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('communes', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->foreignId('region_id')->constrained('regions')->onDelete('cascade');
            $table->string('department', 100)->nullable();

            // Localisation
            $table->decimal('location_lat', 10, 8)->nullable();
            $table->decimal('location_long', 11, 8)->nullable();

            // Statistiques
            $table->integer('population')->nullable();
            $table->decimal('area_km2', 10, 2)->nullable();

            // Risques
            $table->boolean('is_high_risk_zone')->default(false);
            $table->json('risk_types')->nullable(); // ['flooding', 'crime', etc.]

            // Contact
            $table->string('mayor_name')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();

            $table->timestamps();

            $table->index('name');
            $table->index('region_id');
            $table->index(['location_lat', 'location_long']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('communes');
    }
};
