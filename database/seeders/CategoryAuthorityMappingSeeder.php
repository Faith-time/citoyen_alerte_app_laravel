<?php

namespace Database\Seeders;

use App\Models\Authority;
use App\Models\Category;
use App\Models\CategoryAuthorityMapping;
use Illuminate\Database\Seeder;

class CategoryAuthorityMappingSeeder extends Seeder
{
    /**
     * Sous-catégorie (nom exact en base) => une ou plusieurs autorités.
     *
     * Toutes les autorités listées pour une même sous-catégorie sont
     * notifiées SIMULTANÉMENT dès la création du signalement (diffusion
     * immédiate), qu'il soit urgent ou non — c'est le cas notamment pour
     * "Accident de la route", qui doit toujours mobiliser police,
     * pompiers et SAMU ensemble.
     *
     * Aucune mairie n'apparaît ici : elles ne sont plus jamais une cible
     * de routage, seulement un accès de supervision en lecture (voir
     * MunicipalityController).
     */
    private array $mapping = [
        // Voirie → AGEROUTE
        'Nids de poule'              => ['AGEROUTE'],
        'Trottoir dégradé'           => ['AGEROUTE'],
        'Signalisation manquante'    => ['AGEROUTE'],
        'Route inondée'              => ['AGEROUTE', 'ONAS'],
        "Dos d'âne dangereux"        => ['AGEROUTE'],

        // Éclairage → SENELEC
        'Lampadaire en panne'        => ['SENELEC'],
        'Câble électrique dangereux' => ['SENELEC'],
        'Zone non éclairée'          => ['SENELEC'],

        // Déchets → UCG
        'Dépôt sauvage'              => ['UCG'],
        'Bac à ordures plein'        => ['UCG'],
        'Décharge illégale'          => ['UCG'],
        'Brûlage de déchets'         => ['UCG', 'Brigade des Sapeurs-Pompiers'],

        // Eau → SDE/SONES (eau potable) ou ONAS (assainissement/drainage)
        "Fuite d'eau"                => ['SDE / SONES'],
        'Inondation'                 => ['ONAS'],
        'Eau stagnante'              => ['ONAS'],
        'Canalisation bouchée'       => ['ONAS'],

        // Sécurité → Police / Gendarmerie / Pompiers / SAMU selon le cas
        'Éclairage insuffisant (danger)' => ['SENELEC', 'Police Nationale'],
        'Lieu abandonné dangereux'       => ['Police Nationale'],
        'Vandalisme'                     => ['Police Nationale'],
        'Accident de la route'           => ['Police Nationale', 'Brigade des Sapeurs-Pompiers', 'SAMU / Protection Civile'],

        // Environnement → DEEC
        "Pollution de l'air"           => ['DEEC'],
        'Pollution sonore'             => ['DEEC', 'Police Nationale'],
        "Abattage d'arbres illégal"    => ['DEEC'],
        "Pollution d'un cours d'eau"   => ['DEEC', 'ONAS'],

        // Bâtiments → Direction des Bâtiments Publics
        'Bâtiment public dégradé'    => ['Direction des Bâtiments Publics'],
        'École en mauvais état'      => ['Direction des Bâtiments Publics'],
        'Centre de santé dégradé'    => ['Direction des Bâtiments Publics'],
        'Marché insalubre'           => ['Direction des Bâtiments Publics', 'UCG'],

        // Transport → CETUD / AGEROUTE
        'Arrêt de bus dégradé'       => ['CETUD'],
        'Route sans piste cyclable'  => ['CETUD', 'AGEROUTE'],
        'Manque de transport'        => ['CETUD'],
    ];

    public function run(): void
    {
        // On repart d'une correspondance propre : plus aucune ancienne
        // ligne (y compris celles pointant vers une mairie) ne subsiste.
        CategoryAuthorityMapping::truncate();

        $missingCategories  = [];
        $missingAuthorities = [];
        $created            = 0;

        foreach ($this->mapping as $categoryName => $authorityNames) {
            $category = Category::where('name', $categoryName)->first();
            if (!$category) {
                $missingCategories[] = $categoryName;
                continue;
            }

            foreach ($authorityNames as $authorityName) {
                $authority = Authority::where('name', $authorityName)
                    ->where('type', '!=', 'municipal')
                    ->first();

                if (!$authority) {
                    $missingAuthorities[] = $authorityName;
                    continue;
                }

                CategoryAuthorityMapping::create([
                    'category_id'  => $category->id,
                    'authority_id' => $authority->id,
                    'region_name'  => null, // valable pour tout le pays
                    'is_default'   => true,
                ]);
                $created++;
            }
        }

        if ($missingCategories) {
            $this->command?->warn('Catégories introuvables (à vérifier le nom exact en base) : '
                . implode(', ', array_unique($missingCategories)));
        }
        if ($missingAuthorities) {
            $this->command?->warn('Autorités introuvables (à vérifier le nom exact en base) : '
                . implode(', ', array_unique($missingAuthorities)));
        }

        $this->command?->info("Mapping catégorie → autorité(s) régénéré : {$created} correspondances créées.");
    }
}
