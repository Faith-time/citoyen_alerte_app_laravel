<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('regions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('code', 10)->unique()->nullable();
            $table->text('description')->nullable();
            $table->integer('population')->nullable();
            $table->decimal('area_km2', 10, 2)->nullable();
            $table->string('capital', 100)->nullable();
            $table->timestamps();

            $table->index('name');
            $table->index('code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('regions');
    }
};
