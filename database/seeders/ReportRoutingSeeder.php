<?php
// database/seeders/ReportRoutingSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Report;

class ReportRoutingSeeder extends Seeder
{
    /**
     * Routing : quelle autorité est responsable de quel signalement
     *
     * Logique métier :
     *   Voirie / routes / trottoirs  → AGEROUTE (id=11)
     *   Électricité / câbles          → SENELEC  (id=12)
     *   Signalisation / sécurité      → Police   (id=16)
     *   Eau / inondations / urgences  → Pompiers (id=17)
     */
    public function run(): void
    {
        $routings = [
            // RPT-001 (Nid de poule)       → AGEROUTE
            ['reference' => 'RPT-2026-001', 'authority_id' => 11, 'status' => 'routed',       'routed_at' => now()->subDays(3), 'resolved_at' => null],
            // RPT-002 (Lampadaire)          → SENELEC
            ['reference' => 'RPT-2026-002', 'authority_id' => 12, 'status' => 'routed',       'routed_at' => now()->subDays(2), 'resolved_at' => null],
            // RPT-003 (Dépôt sauvage)      → AGEROUTE (en cours)
            ['reference' => 'RPT-2026-003', 'authority_id' => 11, 'status' => 'acknowledged', 'routed_at' => now()->subDays(4), 'resolved_at' => null],
            // RPT-004 (Fuite d'eau)         → Pompiers
            ['reference' => 'RPT-2026-004', 'authority_id' => 17, 'status' => 'routed',       'routed_at' => now()->subDays(1), 'resolved_at' => null],
            // RPT-005 (Route inondée)       → AGEROUTE (résolu)
            ['reference' => 'RPT-2026-005', 'authority_id' => 11, 'status' => 'resolved',     'routed_at' => now()->subDays(7), 'resolved_at' => now()->subDays(1)],
            // RPT-006 (Signalisation)       → Police
            ['reference' => 'RPT-2026-006', 'authority_id' => 16, 'status' => 'routed',       'routed_at' => now()->subDays(3), 'resolved_at' => null],
            // RPT-007 (Câble électrique)    → SENELEC (en cours)
            ['reference' => 'RPT-2026-007', 'authority_id' => 12, 'status' => 'acknowledged', 'routed_at' => now()->subDays(2), 'resolved_at' => null],
            // RPT-008 (Trottoir)            → AGEROUTE
            ['reference' => 'RPT-2026-008', 'authority_id' => 11, 'status' => 'routed',       'routed_at' => now()->subDays(5), 'resolved_at' => null],
        ];

        foreach ($routings as $routing) {
            $report = Report::where('reference', $routing['reference'])->first();

            if (!$report) {
                $this->command->warn("⚠️  Signalement {$routing['reference']} introuvable — ignoré");
                continue;
            }

            DB::table('report_routing')->insert([
                'report_id'    => $report->id,
                'authority_id' => $routing['authority_id'],
                'status'       => $routing['status'],
                'routed_at'    => $routing['routed_at'],
                'resolved_at'  => $routing['resolved_at'],
                'created_at'   => $routing['routed_at'],
                'updated_at'   => now(),
            ]);
        }

        $this->command->info('✅ ReportRoutingSeeder : 8 routings créés');
    }
}
