<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            // Vérifier si commune_name n'existe pas avant de l'ajouter
            if (!Schema::hasColumn('reports', 'commune_name')) {
                $table->string('commune_name', 100)->nullable()->after('address');
                $table->index('commune_name');
            }

            // Vérifier si region_name n'existe pas avant de l'ajouter
            if (!Schema::hasColumn('reports', 'region_name')) {
                $table->string('region_name', 100)->nullable()->after('commune_name');
                $table->index('region_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            if (Schema::hasColumn('reports', 'commune_name')) {
                $table->dropIndex(['commune_name']);
                $table->dropColumn('commune_name');
            }

            if (Schema::hasColumn('reports', 'region_name')) {
                $table->dropIndex(['region_name']);
                $table->dropColumn('region_name');
            }
        });
    }
};
