<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_authorities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('categories')->onDelete('cascade');
            $table->foreignId('subcategory_id')->nullable()->constrained('categories')->onDelete('cascade');
            $table->foreignId('authority_id')->constrained('authorities')->onDelete('cascade');

            $table->enum('role', ['primary', 'secondary', 'support']);
            $table->integer('priority')->default(1);

            // Conditions d'application
            $table->json('conditions')->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->index('category_id');
            $table->index('subcategory_id');
            $table->index('authority_id');
            $table->index('role');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_authorities');
    }
};
