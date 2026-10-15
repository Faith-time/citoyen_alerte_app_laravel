<?php

/**
 * MIGRATION 2 : Migrer les données de communes/regions vers les colonnes texte
 * Fichier : 2026_01_30_002_migrate_location_data.php
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Migrer les noms depuis les tables communes et regions
        DB::statement("
            UPDATE reports r
            LEFT JOIN communes c ON r.commune_id = c.id
            LEFT JOIN regions reg ON r.region_id = reg.id
            SET
                r.commune_name = c.name,
                r.region_name = reg.name
        ");
    }

    public function down(): void
    {
        // Optionnel : remettre les IDs depuis les noms
        DB::statement("
            UPDATE reports r
            LEFT JOIN communes c ON r.commune_name = c.name
            LEFT JOIN regions reg ON r.region_name = reg.name
            SET
                r.commune_id = c.id,
                r.region_id = reg.id
        ");
    }
};
