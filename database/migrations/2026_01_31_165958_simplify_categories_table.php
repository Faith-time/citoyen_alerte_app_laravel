<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            // Supprimer les colonnes inutiles
            $columnsToRemove = [
                'slug',
                'color',
                'sla_hours',
                'requires_specialist',
                'requires_photo',
                'severity_mandatory',
                'display_order'
            ];

            foreach ($columnsToRemove as $column) {
                if (Schema::hasColumn('categories', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('slug', 100)->nullable();
            $table->string('color', 7)->nullable();
            $table->integer('sla_hours')->default(24);
            $table->boolean('requires_specialist')->default(false);
            $table->boolean('requires_photo')->default(true);
            $table->boolean('severity_mandatory')->default(true);
            $table->integer('display_order')->default(0);
        });
    }
};
