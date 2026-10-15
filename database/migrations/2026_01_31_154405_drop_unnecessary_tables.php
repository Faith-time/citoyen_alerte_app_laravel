<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Supprimer les tables dans l'ordre (foreign keys en premier)
        $tablesToDrop = [
            'intervention_updates',
            'interventions',
            'provider_notifications',
            'user_badges',
            'penalties',
            'votes',
            'comments',
            'appeals',
            'notifications',
            'external_agents',
            'equipment',
            'vehicles',
            'emergency_contacts',
            'announcements',
            // 'audit_logs', // Décommenter si vous voulez aussi supprimer les logs
        ];

        foreach ($tablesToDrop as $table) {
            Schema::dropIfExists($table);
        }
    }

    public function down(): void
    {
        // Impossible de restaurer facilement
        // Assurez-vous d'avoir un backup !
        throw new Exception('Cannot rollback this migration. Restore from backup if needed.');
    }
};
