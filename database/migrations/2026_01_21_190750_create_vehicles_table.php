<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('registration_number', 20)->unique();
            $table->string('model', 100);
            $table->string('brand', 50)->nullable();
            $table->integer('year')->nullable();
            $table->enum('type', ['car', 'truck', 'motorcycle', 'van'])->nullable();

            $table->enum('status', ['available', 'in_use', 'maintenance', 'out_of_service'])->default('available');

            $table->foreignId('assigned_agent_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('assigned_at')->nullable();

            // Suivi
            $table->integer('fuel_level')->nullable();
            $table->integer('mileage')->nullable();

            // Maintenance
            $table->timestamp('last_maintenance_at')->nullable();
            $table->timestamp('next_maintenance_at')->nullable();
            $table->date('insurance_expiry')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index('registration_number');
            $table->index('status');
            $table->index('assigned_agent_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
