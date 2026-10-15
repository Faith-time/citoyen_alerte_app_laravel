<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            // Supprimer les foreign keys si elles existent
            if (Schema::hasColumn('reports', 'commune_id')) {
                $table->dropForeign(['commune_id']);
                $table->dropColumn('commune_id');
            }

            if (Schema::hasColumn('reports', 'region_id')) {
                $table->dropForeign(['region_id']);
                $table->dropColumn('region_id');
            }
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
