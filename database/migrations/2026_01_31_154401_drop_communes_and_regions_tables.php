<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Supprimer les tables
        Schema::dropIfExists('communes');
        Schema::dropIfExists('regions');
    }

    public function down(): void
    {
        // Recréer les tables (structure basique pour rollback)
        Schema::create('regions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('code', 10)->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('communes', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->foreignId('region_id')->constrained('regions');
            $table->string('department', 100)->nullable();
            $table->timestamps();
        });
    }
};
