<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Migrer les noms depuis les tables communes et regions vers reports
        if (Schema::hasTable('communes') && Schema::hasTable('regions')) {
            DB::statement("
                UPDATE reports r
                LEFT JOIN communes c ON r.commune_id = c.id
                LEFT JOIN regions reg ON r.region_id = reg.id
                SET
                    r.commune_name = c.name,
                    r.region_name = reg.name
                WHERE r.commune_id IS NOT NULL OR r.region_id IS NOT NULL
            ");
        }
    }

    public function down(): void
    {
        // Optionnel : remettre les IDs depuis les noms
        if (Schema::hasTable('communes') && Schema::hasTable('regions')) {
            DB::statement("
                UPDATE reports r
                LEFT JOIN communes c ON r.commune_name = c.name
                LEFT JOIN regions reg ON r.region_name = reg.name
                SET
                    r.commune_id = c.id,
                    r.region_id = reg.id
                WHERE r.commune_name IS NOT NULL OR r.region_name IS NOT NULL
            ");
        }
    }
};
