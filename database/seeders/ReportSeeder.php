<?php
// database/seeders/ReportSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Report;

class ReportSeeder extends Seeder
{
    public function run(): void
    {
        $reports = [
            // ── Voirie (category_id=2 Nids de poule) → AGEROUTE (id=11) ──
            [
                'reference'    => 'RPT-2026-001',
                'citizen_id'   => 1,
                'category_id'  => 2,
                'title'        => 'Nid de poule dangereux avenue Bourguiba',
                'description'  => 'Nid de poule profond causant des accidents sur l\'avenue Bourguiba',
                'status'       => 'routed',
                'severity'     => 'high',
                'commune_name' => 'Dakar Plateau',
                'region_name'  => 'Dakar',
                'latitude'     => 14.6937,
                'longitude'    => -17.4441,
                'address'      => 'Avenue Bourguiba, Dakar',
                'created_at'   => now()->subDays(3),
                'updated_at'   => now(),
            ],
            // ── Éclairage (category_id=8 Lampadaire en panne) → SENELEC (id=12) ──
            [
                'reference'    => 'RPT-2026-002',
                'citizen_id'   => 1,
                'category_id'  => 8,
                'title'        => 'Lampadaire en panne rue de Thiong',
                'description'  => 'Plusieurs lampadaires éteints depuis 2 semaines, zone sombre et dangereuse',
                'status'       => 'routed',
                'severity'     => 'medium',
                'commune_name' => 'Médina',
                'region_name'  => 'Dakar',
                'latitude'     => 14.6892,
                'longitude'    => -17.4396,
                'address'      => 'Rue de Thiong, Médina',
                'created_at'   => now()->subDays(2),
                'updated_at'   => now(),
            ],
            // ── Déchets (category_id=12 Dépôt sauvage) → AGEROUTE ──
            [
                'reference'    => 'RPT-2026-003',
                'citizen_id'   => 1,
                'category_id'  => 12,
                'title'        => 'Dépôt sauvage marché Sandaga',
                'description'  => 'Accumulation de déchets non collectés depuis 5 jours devant le marché',
                'status'       => 'in_progress',
                'severity'     => 'high',
                'commune_name' => 'Dakar Plateau',
                'region_name'  => 'Dakar',
                'latitude'     => 14.6815,
                'longitude'    => -17.4378,
                'address'      => 'Marché Sandaga, Dakar Plateau',
                'created_at'   => now()->subDays(5),
                'updated_at'   => now(),
            ],
            // ── Eau (category_id=17 Fuite d'eau) → Pompiers (id=17) ──
            [
                'reference'    => 'RPT-2026-004',
                'citizen_id'   => 1,
                'category_id'  => 17,
                'title'        => 'Fuite d\'eau importante HLM',
                'description'  => 'Fuite d\'eau sous forte pression depuis la nuit dernière, chaussée inondée',
                'status'       => 'routed',
                'severity'     => 'high',
                'commune_name' => 'HLM',
                'region_name'  => 'Dakar',
                'latitude'     => 14.7103,
                'longitude'    => -17.4612,
                'address'      => 'Cité HLM, Dakar',
                'created_at'   => now()->subDays(1),
                'updated_at'   => now(),
            ],
            // ── Voirie (category_id=5 Route inondée) → AGEROUTE → RÉSOLU ──
            [
                'reference'    => 'RPT-2026-005',
                'citizen_id'   => 1,
                'category_id'  => 5,
                'title'        => 'Route inondée VDN',
                'description'  => 'La route est complètement inondée suite aux pluies, circulation impossible',
                'status'       => 'resolved',
                'severity'     => 'medium',
                'commune_name' => 'Ouakam',
                'region_name'  => 'Dakar',
                'latitude'     => 14.7256,
                'longitude'    => -17.4901,
                'address'      => 'VDN, Ouakam',
                'resolved_at'  => now()->subDays(1),
                'created_at'   => now()->subDays(7),
                'updated_at'   => now()->subDays(1),
            ],
            // ── Voirie (category_id=4 Signalisation) → Police (id=16) ──
            [
                'reference'    => 'RPT-2026-006',
                'citizen_id'   => 1,
                'category_id'  => 4,
                'title'        => 'Signalisation manquante carrefour Petersen',
                'description'  => 'Panneau stop manquant au carrefour, risque élevé d\'accident',
                'status'       => 'routed',
                'severity'     => 'medium',
                'commune_name' => 'Dakar Plateau',
                'region_name'  => 'Dakar',
                'latitude'     => 14.6731,
                'longitude'    => -17.4389,
                'address'      => 'Carrefour Petersen, Dakar',
                'created_at'   => now()->subDays(4),
                'updated_at'   => now(),
            ],
            // ── Éclairage (category_id=9 Câble électrique) → SENELEC ──
            [
                'reference'    => 'RPT-2026-007',
                'citizen_id'   => 1,
                'category_id'  => 9,
                'title'        => 'Câble électrique à découvert Médina',
                'description'  => 'Câble haute tension tombé sur la voie publique, danger immédiat',
                'status'       => 'in_progress',
                'severity'     => 'high',
                'commune_name' => 'Médina',
                'region_name'  => 'Dakar',
                'latitude'     => 14.6879,
                'longitude'    => -17.4412,
                'address'      => 'Médina, Dakar',
                'created_at'   => now()->subDays(2),
                'updated_at'   => now(),
            ],
            // ── Voirie (category_id=3 Trottoir) → AGEROUTE ──
            [
                'reference'    => 'RPT-2026-008',
                'citizen_id'   => 1,
                'category_id'  => 3,
                'title'        => 'Trottoir dégradé avenue Cheikh Anta Diop',
                'description'  => 'Trottoir complètement effondré sur 20m, dangereux pour les piétons',
                'status'       => 'routed',
                'severity'     => 'low',
                'commune_name' => 'Fann',
                'region_name'  => 'Dakar',
                'latitude'     => 14.6943,
                'longitude'    => -17.4725,
                'address'      => 'Avenue Cheikh Anta Diop, Fann',
                'created_at'   => now()->subDays(6),
                'updated_at'   => now(),
            ],
        ];

        foreach ($reports as $data) {
            Report::create($data);
        }

        $this->command->info('✅ ReportSeeder : 8 signalements créés (citizen_id=1)');
    }
}
