<?php
// database/seeders/CategorySeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $parents = [
            ['name' => 'Voirie',        'description' => 'Problèmes liés aux routes et chaussées',    'icon' => 'road'],
            ['name' => 'Éclairage',     'description' => 'Problèmes d\'éclairage public',              'icon' => 'lightbulb'],
            ['name' => 'Déchets',       'description' => 'Dépôts sauvages et collecte des ordures',    'icon' => 'trash'],
            ['name' => 'Eau',           'description' => 'Fuites, inondations et réseaux d\'eau',      'icon' => 'water'],
            ['name' => 'Sécurité',      'description' => 'Incidents de sécurité publique',             'icon' => 'shield'],
            ['name' => 'Environnement', 'description' => 'Pollution et dégradation environnementale', 'icon' => 'leaf'],
            ['name' => 'Bâtiments',     'description' => 'Bâtiments publics et infrastructures',      'icon' => 'building'],
            ['name' => 'Transport',     'description' => 'Transports en commun et mobilité',           'icon' => 'bus'],
        ];

        $children = [
            'Voirie' => [
                'Nids de poule',
                'Trottoir dégradé',
                'Signalisation manquante',
                'Route inondée',
                'Dos d\'âne dangereux',
            ],
            'Éclairage' => [
                'Lampadaire en panne',
                'Câble électrique dangereux',
                'Zone non éclairée',
            ],
            'Déchets' => [
                'Dépôt sauvage',
                'Bac à ordures plein',
                'Décharge illégale',
                'Brûlage de déchets',
            ],
            'Eau' => [
                'Fuite d\'eau',
                'Inondation',
                'Eau stagnante',
                'Canalisation bouchée',
            ],
            'Sécurité' => [
                'Éclairage insuffisant (danger)',
                'Lieu abandonné dangereux',
                'Vandalisme',
                'Accident de la route',
            ],
            'Environnement' => [
                'Pollution de l\'air',
                'Pollution sonore',
                'Abattage d\'arbres illégal',
                'Pollution d\'un cours d\'eau',
            ],
            'Bâtiments' => [
                'Bâtiment public dégradé',
                'École en mauvais état',
                'Centre de santé dégradé',
                'Marché insalubre',
            ],
            'Transport' => [
                'Arrêt de bus dégradé',
                'Route sans piste cyclable',
                'Manque de transport',
            ],
        ];

        // Insérer parents
        foreach ($parents as $parent) {
            $id = DB::table('categories')->insertGetId([
                'name'        => $parent['name'],
                'description' => $parent['description'],
                'icon'        => $parent['icon'],
                'parent_id'   => null,
                'is_active'   => true,
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);

            // Insérer sous-catégories
            foreach ($children[$parent['name']] as $childName) {
                DB::table('categories')->insert([
                    'name'       => $childName,
                    'parent_id'  => $id,
                    'is_active'  => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }
}
