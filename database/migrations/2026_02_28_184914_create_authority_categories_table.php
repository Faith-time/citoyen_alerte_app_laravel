<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Table pivot : quelle autorité est compétente pour quelle catégorie
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('authority_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('authority_id')
                ->constrained('authorities')
                ->onDelete('cascade');
            $table->foreignId('category_id')
                ->constrained('categories')
                ->onDelete('cascade');
            $table->boolean('is_primary')->default(true); // autorité principale vs secondaire
            $table->timestamps();

            $table->unique(['authority_id', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('authority_categories');
    }
};
