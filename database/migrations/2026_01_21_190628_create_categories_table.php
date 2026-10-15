<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 100)->unique();
            $table->text('description')->nullable();

            // Hiérarchie (pour sous-catégories)
            $table->foreignId('parent_id')->nullable()->constrained('categories')->onDelete('set null');

            // Apparence
            $table->string('icon', 50)->nullable();
            $table->string('color', 7)->nullable();

            // Configuration
            $table->integer('sla_hours')->default(24);
            $table->boolean('requires_specialist')->default(false);
            $table->boolean('requires_photo')->default(true);
            $table->boolean('severity_mandatory')->default(true);

            // Statut
            $table->boolean('is_active')->default(true);
            $table->integer('display_order')->default(0);

            $table->timestamps();

            $table->index('slug');
            $table->index('parent_id');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
