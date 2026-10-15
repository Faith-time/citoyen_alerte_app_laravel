<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained('reports')->onDelete('cascade');
            $table->foreignId('authority_id')->constrained('authorities')->onDelete('cascade');
            $table->foreignId('routing_id')->nullable()->constrained('report_routing')->onDelete('cascade');

            $table->enum('notification_type', ['email', 'sms', 'api', 'webhook']);
            $table->text('content')->nullable();

            $table->enum('status', ['pending', 'sent', 'delivered', 'failed', 'bounced'])->default('pending');
            $table->text('error_message')->nullable();

            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('report_id');
            $table->index('authority_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_notifications');
    }
};
