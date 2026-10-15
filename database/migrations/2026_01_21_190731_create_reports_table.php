<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 20)->unique();

            // Relations
            $table->foreignId('citizen_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('category_id')->constrained('categories')->onDelete('restrict');

            // Contenu
            $table->string('title');
            $table->text('description');

            // Localisation
            $table->decimal('location_lat', 10, 8);
            $table->decimal('location_long', 11, 8);
            $table->text('address')->nullable();
            $table->foreignId('commune_id')->constrained('communes')->onDelete('restrict');
            $table->foreignId('region_id')->constrained('regions')->onDelete('restrict');

            // Classification
            $table->enum('severity', ['low', 'medium', 'high', 'urgent']);
            $table->enum('status', [
                'pending', 'validated', 'assigned', 'routed_to_authority',
                'in_progress', 'resolved', 'closed', 'rejected', 'duplicate'
            ])->default('pending');

            // Anonymat
            $table->boolean('is_anonymous')->default(false);

            // Validation & Vérification
            $table->boolean('is_verified')->default(false);
            $table->foreignId('verified_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('verified_at')->nullable();
            $table->enum('actual_severity', ['low', 'medium', 'high', 'urgent'])->nullable();

            // Fraude & Modération
            $table->boolean('is_flagged')->default(false);
            $table->string('flag_reason', 100)->nullable();
            $table->boolean('is_fake')->default(false);
            $table->text('rejection_reason')->nullable();
            $table->boolean('requires_admin_review')->default(false);
            $table->boolean('admin_verified')->default(false);

            // Assignation
            $table->foreignId('assigned_agent_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('accepted_at')->nullable();

            // Routage vers autorités
            $table->foreignId('authority_id')->nullable()->constrained('authorities')->onDelete('set null');
            $table->timestamp('routed_at')->nullable();

            // Statistiques
            $table->integer('views_count')->default(0);
            $table->integer('upvotes')->default(0);
            $table->integer('downvotes')->default(0);
            $table->integer('comments_count')->default(0);

            // Résolution
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->onDelete('set null');

            // Priorité calculée
            $table->integer('priority_score')->default(0);

            $table->timestamps();

            $table->index('reference');
            $table->index('citizen_id');
            $table->index('category_id');
            $table->index('status');
            $table->index('severity');
            $table->index('commune_id');
            $table->index('assigned_agent_id');
            $table->index('authority_id');
            $table->index(['location_lat', 'location_long']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
