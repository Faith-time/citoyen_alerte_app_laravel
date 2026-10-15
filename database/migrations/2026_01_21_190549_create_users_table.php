<?php
// database/migrations/2024_01_01_000003_create_users_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone', 20)->unique();
            $table->string('password');
            $table->enum('role', ['citizen', 'agent', 'external_agent', 'admin'])->default('citizen');
            $table->string('avatar')->nullable();

            // Vérification & Sécurité
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('phone_verified_at')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_active')->default(true);

            // Localisation
            $table->decimal('location_lat', 10, 8)->nullable();
            $table->decimal('location_long', 11, 8)->nullable();
            $table->text('address')->nullable();
            $table->foreignId('commune_id')->nullable()->constrained('communes')->onDelete('set null');
            $table->foreignId('region_id')->nullable()->constrained('regions')->onDelete('set null');

            // Système de crédits & Réputation (Citoyens)
            $table->integer('credits')->default(10);
            $table->integer('max_credits')->default(10);
            $table->integer('reputation_score')->default(50);
            $table->integer('fake_reports_count')->default(0);

            // Sanctions
            $table->boolean('is_suspended')->default(false);
            $table->timestamp('suspended_until')->nullable();
            $table->text('suspension_reason')->nullable();
            $table->boolean('is_banned')->default(false);
            $table->text('ban_reason')->nullable();
            $table->timestamp('banned_at')->nullable();

            // Préférences
            $table->json('notification_preferences')->nullable();
            $table->string('language', 10)->default('fr'); // 'fr', 'wo', 'en'

            // Tokens
            $table->rememberToken();
            $table->string('fcm_token')->nullable(); // Firebase Cloud Messaging

            // Timestamps
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();

            $table->index('email');
            $table->index('phone');
            $table->index('role');
            $table->index('commune_id');
            $table->index(['location_lat', 'location_long']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
