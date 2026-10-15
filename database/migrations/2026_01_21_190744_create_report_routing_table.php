<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_routing', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained('reports')->onDelete('cascade');
            $table->foreignId('authority_id')->constrained('authorities')->onDelete('cascade');
            $table->foreignId('assigned_external_agent_id')->nullable()->constrained('external_agents')->onDelete('set null');

            $table->string('routing_reason', 100)->nullable();
            $table->enum('status', [
                'routed', 'acknowledged', 'assigned', 'in_progress', 'resolved', 'transferred', 'rejected'
            ])->default('routed');

            // Timeline
            $table->timestamp('routed_at')->useCurrent();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('resolved_at')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index('report_id');
            $table->index('authority_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_routing');
    }
};
