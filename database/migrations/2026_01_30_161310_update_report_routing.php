<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateReportRouting extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('report_routing', function (Blueprint $table) {

            // Ajouter la colonne routed_by si elle n'existe pas
            if (!Schema::hasColumn('report_routing', 'routed_by')) {
                $table->foreignId('routed_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete()
                    ->after('authority_id');
            }

            // Modifier la colonne status
            $table->enum('status', [
                'routed',
                'acknowledged',
                'assigned',
                'in_progress',
                'resolved',
                'rejected',
                'closed'
            ])->default('routed')->change();

            // Ajouter rejected_at si elle n'existe pas
            if (!Schema::hasColumn('report_routing', 'rejected_at')) {
                $table->timestamp('rejected_at')->nullable()->after('resolved_at');
            }

            // Ajouter closed_at si elle n'existe pas
            if (!Schema::hasColumn('report_routing', 'closed_at')) {
                $table->timestamp('closed_at')->nullable()->after('rejected_at');
            }

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('report_routing', function (Blueprint $table) {

            if (Schema::hasColumn('report_routing', 'routed_by')) {
                $table->dropForeign(['routed_by']);
                $table->dropColumn('routed_by');
            }

            // Impossible de revert un enum facilement
            // Donc on laisse la colonne status telle quelle

            if (Schema::hasColumn('report_routing', 'rejected_at')) {
                $table->dropColumn('rejected_at');
            }

            if (Schema::hasColumn('report_routing', 'closed_at')) {
                $table->dropColumn('closed_at');
            }

        });
    }
}

