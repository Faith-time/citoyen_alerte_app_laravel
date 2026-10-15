<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('badges', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 100)->unique();
            $table->text('description')->nullable();
            $table->string('icon')->nullable();

            $table->enum('category', ['citizen', 'agent', 'contribution', 'performance'])->nullable();

            // Critères d'obtention
            $table->integer('points_required')->default(0);
            $table->integer('reports_required')->default(0);
            $table->json('conditions')->nullable();

            // Rareté
            $table->enum('rarity', ['common', 'rare', 'epic', 'legendary'])->default('common');

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index('slug');
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('badges');
    }
};
