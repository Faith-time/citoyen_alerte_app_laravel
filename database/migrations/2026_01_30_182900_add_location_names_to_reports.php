<?php

/**
 * MIGRATION 1 : Ajouter commune_name et region_name dans reports
 * Fichier : 2026_01_30_001_add_location_names_to_reports.php
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->string('commune_name', 100)->nullable()->after('address');
            $table->string('region_name', 100)->nullable()->after('commune_name');

            // Ajouter des index pour les recherches
            $table->index('commune_name');
            $table->index('region_name');
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropIndex(['commune_name']);
            $table->dropIndex(['region_name']);
            $table->dropColumn(['commune_name', 'region_name']);
        });
    }
};
