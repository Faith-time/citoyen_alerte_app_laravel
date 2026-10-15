<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Supprimer les colonnes de localisation
            $columnsToRemove = [
                'location_lat',
                'location_long',
                'address',
                'commune_id',
                'region_id'
            ];

            foreach ($columnsToRemove as $column) {
                if (Schema::hasColumn('users', $column)) {
                    if (in_array($column, ['commune_id', 'region_id'])) {
                        // Supprimer foreign key d'abord
                        try {
                            $table->dropForeign(['commune_id']);
                        } catch (\Exception $e) {
                            // Foreign key n'existe peut-être pas
                        }
                    }
                    $table->dropColumn($column);
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
            $table->foreignId('commune_id')->nullable()->constrained();
            $table->foreignId('region_id')->nullable()->constrained();
        });
    }
};
