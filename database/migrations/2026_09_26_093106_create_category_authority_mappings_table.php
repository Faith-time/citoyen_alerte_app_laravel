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
        Schema::create('category_authority_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('authority_id')->constrained()->cascadeOnDelete();
            $table->string('region_name')->nullable(); // null = valable pour toutes les régions
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->unique(['category_id', 'region_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('category_authority_mappings');
    }
};
