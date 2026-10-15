<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emergency_contacts', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('category', 50);

            $table->string('phone', 20);
            $table->string('alternative_phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();

            // Localisation
            $table->decimal('location_lat', 10, 8)->nullable();
            $table->decimal('location_long', 11, 8)->nullable();
            $table->foreignId('commune_id')->nullable()->constrained('communes')->onDelete('set null');
            $table->foreignId('region_id')->nullable()->constrained('regions')->onDelete('set null');

            // Disponibilité
            $table->boolean('is_24_7')->default(false);
            $table->string('working_hours', 100)->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index('category');
            $table->index('commune_id');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emergency_contacts');
    }
};
