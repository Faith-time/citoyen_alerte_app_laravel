<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('authorities', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('acronym', 20)->nullable();
            $table->enum('type', ['government', 'company', 'municipal', 'security', 'health', 'environment']);
            $table->text('description')->nullable();

            // Contact
            $table->string('logo_path')->nullable();
            $table->string('website')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('emergency_phone', 20)->nullable();

            // API Integration
            $table->string('api_endpoint')->nullable();
            $table->text('api_key')->nullable();
            $table->boolean('api_active')->default(false);

            // Portée
            $table->enum('scope', ['national', 'regional', 'municipal']);
            $table->json('regions')->nullable();

            // Statut
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index('type');
            $table->index('scope');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('authorities');
    }
};
