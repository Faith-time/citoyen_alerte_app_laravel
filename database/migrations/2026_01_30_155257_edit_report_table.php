<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();

            $table->string('reference')->unique();

            $table->foreignId('citizen_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('category_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('title', 150);
            $table->text('description');

            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->string('address')->nullable();

            $table->foreignId('commune_id')->nullable()->constrained();
            $table->foreignId('region_id')->nullable()->constrained();

            $table->enum('severity', ['low', 'medium', 'high', 'urgent'])
                ->default('medium');

            $table->enum('status', [
                'pending',
                'validated',
                'assigned',
                'in_progress',
                'resolved',
                'rejected'
            ])->default('pending');

            $table->boolean('is_anonymous')->default(false);

            $table->timestamp('resolved_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
