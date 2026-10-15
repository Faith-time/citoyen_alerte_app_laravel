<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Région de la mairie — permet de filtrer les signalements de sa zone
        // en lecture seule, sans passer par un routage manuel.
        Schema::table('authorities', function (Blueprint $table) {
            if (!Schema::hasColumn('authorities', 'region_name')) {
                $table->string('region_name')->nullable()->after('type');
            }
        });

        // Disponibilité d'un agent (utilisé pour l'assignation et, plus tard,
        // pour cibler les notifications d'urgence aux seuls agents de garde).
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'is_on_duty')) {
                $table->boolean('is_on_duty')->default(true)->after('authority_id');
            }
        });

        // Accusé de réception + escalade, au niveau de CHAQUE routage
        // (un signalement urgent peut être routé vers plusieurs autorités
        // à la fois, chacune accuse réception indépendamment).
        Schema::table('report_routing', function (Blueprint $table) {
            if (!Schema::hasColumn('report_routing', 'acknowledged_at')) {
                $table->timestamp('acknowledged_at')->nullable()->after('status');
            }
            if (!Schema::hasColumn('report_routing', 'acknowledged_by')) {
                $table->unsignedBigInteger('acknowledged_by')->nullable()->after('acknowledged_at');
                $table->foreign('acknowledged_by')->references('id')->on('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('report_routing', 'escalated_at')) {
                $table->timestamp('escalated_at')->nullable()->after('acknowledged_by');
            }
            if (!Schema::hasColumn('report_routing', 'escalation_level')) {
                $table->unsignedTinyInteger('escalation_level')->default(0)->after('escalated_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('report_routing', function (Blueprint $table) {
            $table->dropForeign(['acknowledged_by']);
            $table->dropColumn(['acknowledged_at', 'acknowledged_by', 'escalated_at', 'escalation_level']);
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_on_duty');
        });
        Schema::table('authorities', function (Blueprint $table) {
            $table->dropColumn('region_name');
        });
    }
};
