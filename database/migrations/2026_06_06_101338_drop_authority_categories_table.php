<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::dropIfExists('authority_categories');
    }

    public function down()
    {
        // Recreate if needed to rollback
        Schema::create('authority_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('authority_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_primary')->default(true);
            $table->timestamps();
        });
    }
};
