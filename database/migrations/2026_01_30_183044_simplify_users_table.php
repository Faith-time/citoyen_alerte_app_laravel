<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Supprimer les FK avant les colonnes
            if (Schema::hasColumn('users', 'commune_id')) {
                $table->dropForeign(['commune_id']);
                $table->dropColumn('commune_id');
            }
            if (Schema::hasColumn('users', 'region_id')) {
                $table->dropForeign(['region_id']);
                $table->dropColumn('region_id');
            }

            // Supprimer les colonnes de localisation
            foreach (['location_lat', 'location_long', 'address', 'is_suspended', 'suspended_until', 'suspension_reason', 'notification_preferences', 'language', 'fcm_token', 'last_login_at'] as $col) {
                if (Schema::hasColumn('users', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('location_lat', 10, 8)->nullable();
            $table->decimal('location_long', 11, 8)->nullable();
            $table->text('address')->nullable();

            $table->boolean('is_suspended')->default(false);
            $table->timestamp('suspended_until')->nullable();
            $table->text('suspension_reason')->nullable();

            $table->json('notification_preferences')->nullable();
            $table->string('language', 10)->default('fr');
            $table->string('fcm_token', 255)->nullable();
            $table->timestamp('last_login_at')->nullable();
        });
    }
};
