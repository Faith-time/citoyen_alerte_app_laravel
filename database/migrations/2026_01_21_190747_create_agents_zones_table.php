<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agents_zones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('commune_id')->constrained('communes')->onDelete('cascade');
            $table->boolean('is_primary')->default(false);

            $table->timestamp('created_at')->useCurrent();

            $table->unique(['agent_id', 'commune_id']);
            $table->index('agent_id');
            $table->index('commune_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agents_zones');
    }
};
