<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Enrichir agents_zones : lier chaque agent à une autorité + zone géographique
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agents_zones', function (Blueprint $table) {
            // Lien vers l'autorité dont dépend l'agent
            $table->foreignId('authority_id')
                ->nullable()
                ->after('agent_id')
                ->constrained('authorities')
                ->onDelete('set null');

            // Zone géographique de compétence
            $table->string('commune_name', 100)->nullable()->after('authority_id');
            $table->string('region_name', 100)->nullable()->after('commune_name');

            // Rayon d'intervention en km
            $table->decimal('radius_km', 5, 2)->default(5.00)->after('region_name');

            // Latitude/longitude du centre de la zone
            $table->decimal('center_lat', 10, 7)->nullable()->after('radius_km');
            $table->decimal('center_lng', 10, 7)->nullable()->after('center_lat');
        });
    }

    public function down(): void
    {
        Schema::table('agents_zones', function (Blueprint $table) {
            $table->dropForeign(['authority_id']);
            $table->dropColumn([
                'authority_id',
                'commune_name',
                'region_name',
                'radius_km',
                'center_lat',
                'center_lng',
            ]);
        });
    }
};
