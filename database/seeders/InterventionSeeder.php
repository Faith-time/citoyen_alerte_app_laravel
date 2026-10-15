<?php
// database/seeders/InterventionSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Intervention;
use App\Models\Report;

class InterventionSeeder extends Seeder
{
    public function run(): void
    {
        // Intervention terminée sur RPT-005 (route inondée — résolu)
        $report005 = Report::where('reference', 'RPT-2026-005')->first();
        if ($report005) {
            Intervention::create([
                'agent_id'     => 2,
                'report_id'    => $report005->id,
                'authority_id' => 11, // AGEROUTE
                'status'       => 'completed',
                'notes'        => 'Intervention terminée : pompage de l\'eau effectué, route dégagée',
                'started_at'   => now()->subDays(6),
                'completed_at' => now()->subDays(1),
                'created_at'   => now()->subDays(6),
                'updated_at'   => now()->subDays(1),
            ]);
        }

        // Intervention en cours sur RPT-003 (dépôt sauvage)
        $report003 = Report::where('reference', 'RPT-2026-003')->first();
        if ($report003) {
            Intervention::create([
                'agent_id'     => 2,
                'report_id'    => $report003->id,
                'authority_id' => 11, // AGEROUTE
                'status'       => 'in_progress',
                'notes'        => 'Équipe de nettoyage sur place, collecte en cours',
                'started_at'   => now()->subDays(4),
                'completed_at' => null,
                'created_at'   => now()->subDays(4),
                'updated_at'   => now(),
            ]);
        }

        // Intervention en attente sur RPT-007 (câble électrique)
        $report007 = Report::where('reference', 'RPT-2026-007')->first();
        if ($report007) {
            Intervention::create([
                'agent_id'     => 2,
                'report_id'    => $report007->id,
                'authority_id' => 12, // SENELEC
                'status'       => 'pending',
                'notes'        => 'Signalement reçu, équipe SENELEC en attente de disponibilité',
                'started_at'   => null,
                'completed_at' => null,
                'created_at'   => now()->subDays(2),
                'updated_at'   => now(),
            ]);
        }

        $this->command->info('✅ InterventionSeeder : 3 interventions créées pour Agent Diallo');
    }
}
