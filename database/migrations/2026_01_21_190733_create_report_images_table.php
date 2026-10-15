<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained('reports')->onDelete('cascade');
            $table->string('image_path');
            $table->enum('type', ['initial', 'before', 'during', 'after']);
            $table->foreignId('uploaded_by')->constrained('users')->onDelete('cascade');
            $table->integer('file_size')->nullable();
            $table->string('mime_type', 50)->nullable();

            // Validation IA
            $table->boolean('is_validated')->default(false);
            $table->decimal('ai_confidence_score', 5, 2)->nullable();
            $table->json('ai_flags')->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->index('report_id');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_images');
    }
};
