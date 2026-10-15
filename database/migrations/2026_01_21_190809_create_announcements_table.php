<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('users')->onDelete('cascade');

            $table->string('title');
            $table->text('content');

            $table->enum('type', ['info', 'warning', 'alert', 'maintenance', 'success'])->default('info');
            $table->enum('target_audience', ['all', 'citizens', 'agents', 'specific'])->default('all');

            // Ciblage géographique
            $table->foreignId('commune_id')->nullable()->constrained('communes')->onDelete('cascade');
            $table->foreignId('region_id')->nullable()->constrained('regions')->onDelete('cascade');

            // Visibilité
            $table->boolean('is_active')->default(true);
            $table->timestamp('published_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            $table->timestamps();

            $table->index('admin_id');
            $table->index('is_active');
            $table->index('published_at');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
