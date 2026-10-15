<?php

/**
 * MIGRATION 3 : Supprimer commune_id et region_id de reports
 * Fichier : 2026_01_30_003_remove_location_foreign_keys.php
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            // Supprimer les foreign keys
            $table->dropForeign(['commune_id']);
            $table->dropForeign(['region_id']);

            // Supprimer les colonnes
            $table->dropColumn(['commune_id', 'region_id']);
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->foreignId('commune_id')->nullable()
                ->constrained('communes')->onDelete('set null');
            $table->foreignId('region_id')->nullable()
                ->constrained('regions')->onDelete('set null');
        });
    }
};
