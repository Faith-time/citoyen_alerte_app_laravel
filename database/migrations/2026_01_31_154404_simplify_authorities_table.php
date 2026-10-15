<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('authorities', function (Blueprint $table) {
            // Colonnes à supprimer
            $columnsToRemove = [
                'acronym',
                'logo_path',
                'emergency_phone',
                'api_endpoint',
                'api_key',
                'api_active',
                'scope',
                'regions'
            ];

            foreach ($columnsToRemove as $column) {
                if (Schema::hasColumn('authorities', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('authorities', function (Blueprint $table) {
            $table->string('acronym', 20)->nullable();
            $table->string('logo_path', 255)->nullable();
            $table->string('emergency_phone', 20)->nullable();
            $table->string('api_endpoint', 255)->nullable();
            $table->text('api_key')->nullable();
            $table->boolean('api_active')->default(false);
            $table->enum('scope', ['national', 'regional', 'local'])->default('local');
            $table->json('regions')->nullable();
        });
    }
};
