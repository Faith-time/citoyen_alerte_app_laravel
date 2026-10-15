<?php

namespace App\Console\Commands;

use App\Models\Authority;
use App\Models\CategoryAuthorityMapping;
use App\Models\Report;
use App\Models\ReportRouting;
use Illuminate\Console\Command;

/**
 * À exécuter UNE SEULE FOIS, après avoir lancé les seeders
 * (AuthoritySeeder + CategoryAuthorityMappingSeeder), pour corriger
 * les données déjà en base :
 *
 *   php artisan reports:fix-routing
 *
 * Ce que ça fait :
 *  1. Supprime tous les routages pointant vers une mairie (elles ne sont
 *     plus jamais une cible de routage).
 *  2. Pour chaque signalement NON résolu, reconstruit ses routages depuis
 *     zéro à partir de la table de correspondance actuelle (une ligne par
 *     autorité compétente — plusieurs si la catégorie en mobilise
 *     plusieurs, comme "Accident de la route").
 *  3. Laisse intacts les signalements déjà résolus (historique).
 */
class FixReportRouting extends Command
{
    protected $signature = 'reports:fix-routing';

    protected $description = 'Supprime les routages vers les mairies et reconstruit le routage de chaque signalement non résolu selon la nouvelle logique multi-autorités.';

    public function handle(): int
    {
        $mairieIds = Authority::where('type', 'municipal')->pluck('id');

        $removedMairie = ReportRouting::whereIn('authority_id', $mairieIds)->delete();
        $this->info("Routages vers des mairies supprimés : {$removedMairie}");

        $reports = Report::where('status', '!=', 'resolved')->get();
        $this->info("Signalements non résolus à reconstruire : {$reports->count()}");

        $fixed    = 0;
        $unmapped = 0;

        foreach ($reports as $report) {
            // On repart d'un état propre pour ce signalement.
            ReportRouting::where('report_id', $report->id)->delete();

            $mappings = CategoryAuthorityMapping::where('category_id', $report->category_id)
                ->where(function ($q) use ($report) {
                    $q->where('region_name', $report->region_name)
                        ->orWhereNull('region_name');
                })
                ->orderByRaw('region_name IS NULL')
                ->get()
                ->unique('authority_id');

            if ($mappings->isEmpty()) {
                $unmapped++;
                $report->update(['status' => 'pending']);
                $this->warn("  - Signalement #{$report->id} ({$report->reference}) : aucune autorité mappée pour sa catégorie, laissé en 'pending'.");
                continue;
            }

            foreach ($mappings as $mapping) {
                ReportRouting::create([
                    'report_id'    => $report->id,
                    'authority_id' => $mapping->authority_id,
                    'status'       => 'routed',
                    'routed_at'    => now(),
                    'notes'        => 'Re-routage automatique (nouvelle logique multi-autorités)',
                ]);
            }

            $report->update(['status' => 'routed']);
            $fixed++;
        }

        $this->info("Signalements re-routés correctement : {$fixed}");
        if ($unmapped > 0) {
            $this->warn("Signalements sans mapping trouvé (à router manuellement en admin) : {$unmapped}");
        }

        return self::SUCCESS;
    }
}
