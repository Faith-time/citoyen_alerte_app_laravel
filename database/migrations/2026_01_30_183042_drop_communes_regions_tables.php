<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {

        $tablesWithCommuneFK = ['agents_zones', 'users', 'announcements', 'emergency_contacts'];

        foreach ($tablesWithCommuneFK as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'commune_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropForeign(['commune_id']);
                    $table->dropColumn('commune_id');
                });
            }
        }

        // ----------------------------
        // Supprimer toutes les FK vers 'regions'
        // ----------------------------
        $tablesWithRegionFK = ['users', 'announcements', 'emergency_contacts'];

        foreach ($tablesWithRegionFK as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'region_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropForeign(['region_id']);
                    $table->dropColumn('region_id');
                });
            }
        }

        // ----------------------------
        // Drop des tables
        // ----------------------------
        Schema::dropIfExists('communes');
        Schema::dropIfExists('regions');
    }

    public function down(): void
    {
        // ----------------------------
        // Recréer regions
        // ----------------------------
        Schema::create('regions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('code', 10)->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // ----------------------------
        // Recréer communes
        // ----------------------------
        Schema::create('communes', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->foreignId('region_id')->constrained('regions')->cascadeOnDelete();
            $table->string('department', 100)->nullable();
            $table->decimal('location_lat', 10, 8)->nullable();
            $table->decimal('location_long', 11, 8)->nullable();
            $table->timestamps();
        });

        // ----------------------------
        // Recréer FK dans les autres tables si nécessaire
        // ----------------------------
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (!Schema::hasColumn('users', 'commune_id')) {
                    $table->foreignId('commune_id')->nullable()->constrained('communes')->cascadeOnDelete();
                }
                if (!Schema::hasColumn('users', 'region_id')) {
                    $table->foreignId('region_id')->nullable()->constrained('regions')->cascadeOnDelete();
                }
            });
        }

        if (Schema::hasTable('announcements')) {
            Schema::table('announcements', function (Blueprint $table) {
                if (!Schema::hasColumn('announcements', 'commune_id')) {
                    $table->foreignId('commune_id')->nullable()->constrained('communes')->cascadeOnDelete();
                }
                if (!Schema::hasColumn('announcements', 'region_id')) {
                    $table->foreignId('region_id')->nullable()->constrained('regions')->cascadeOnDelete();
                }
            });
        }

        if (Schema::hasTable('emergency_contacts')) {
            Schema::table('emergency_contacts', function (Blueprint $table) {
                if (!Schema::hasColumn('emergency_contacts', 'commune_id')) {
                    $table->foreignId('commune_id')->nullable()->constrained('communes')->cascadeOnDelete();
                }
                if (!Schema::hasColumn('emergency_contacts', 'region_id')) {
                    $table->foreignId('region_id')->nullable()->constrained('regions')->cascadeOnDelete();
                }
            });
        }
    }
};
