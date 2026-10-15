<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Liste des colonnes à supprimer
            $columnsToRemove = [
                'credits',
                'max_credits',
                'reputation_score',
                'fake_reports_count',
                'is_suspended',
                'suspended_until',
                'suspension_reason',
                'notification_preferences',
                'language',
                'fcm_token',
                'last_login_at',
                'remember_token'
            ];

            foreach ($columnsToRemove as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->integer('credits')->default(10);
            $table->integer('max_credits')->default(10);
            $table->integer('reputation_score')->default(50);
            $table->integer('fake_reports_count')->default(0);
            $table->boolean('is_suspended')->default(false);
            $table->timestamp('suspended_until')->nullable();
            $table->text('suspension_reason')->nullable();
            $table->json('notification_preferences')->nullable();
            $table->string('language', 10)->default('fr');
            $table->string('fcm_token', 255)->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
        });
    }
};
