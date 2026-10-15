<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('intervention_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('intervention_id')->constrained('interventions')->onDelete('cascade');
            $table->enum('status', ['en_route', 'on_site', 'in_treatment', 'paused', 'completed']);
            $table->text('comment')->nullable();

            // Localisation de l'agent
            $table->decimal('location_lat', 10, 8)->nullable();
            $table->decimal('location_long', 11, 8)->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->index('intervention_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('intervention_updates');
    }
};
