<?php

namespace Database\Seeders;

use App\Models\Authority;
use Illuminate\Database\Seeder;

class AuthoritySeeder extends Seeder
{
    /**
     * Mairies déjà existantes en base : on complète juste leur région,
     * sans toucher aux comptes users déjà créés (role=admin, authority_id).
     */
    private array $mairieRegions = [
        'Mairie de Dakar'       => 'Dakar',
        'Mairie de Thiès'       => 'Thiès',
        'Mairie de Diourbel'    => 'Diourbel',
        'Mairie de Saint-Louis' => 'Saint-Louis',
        'Mairie de Louga'       => 'Louga',
        'Mairie de Fatick'      => 'Fatick',
        'Mairie de Kaolack'     => 'Kaolack',
        'Mairie de Kaffrine'    => 'Kaffrine',
        'Mairie de Kédougou'    => 'Kédougou',
        'Mairie de Kolda'       => 'Kolda',
        'Mairie de Matam'       => 'Matam',
        'Mairie de Sédhiou'     => 'Sédhiou',
        'Mairie de Tambacounda' => 'Tambacounda',
        'Mairie de Ziguinchor'  => 'Ziguinchor',
    ];

    /**
     * Nouvelles autorités spécialisées nécessaires pour couvrir les cas
     * d'urgence (accidents) et les catégories qui n'avaient pas encore
     * d'autorité compétente (Bâtiments, Transport).
     *
     * Le boot() du modèle Authority crée automatiquement le compte user
     * (role=authority) associé dès la création — comportement existant,
     * conservé ici.
     */
    private array $newAuthorities = [
        [
            'name'        => 'SAMU / Protection Civile',
            'type'        => 'government',
            'description' => 'Urgences médicales et secours aux personnes',
            'email'       => 'contact@samu.sn',
            'phone'       => '1515',
            'is_active'   => true,
        ],
        [
            'name'        => 'CETUD',
            'type'        => 'government',
            'description' => 'Conseil Exécutif des Transports Urbains — transport public et infrastructures associées',
            'email'       => 'contact@cetud.sn',
            'is_active'   => true,
        ],
        [
            'name'        => 'Direction des Bâtiments Publics',
            'type'        => 'government',
            'description' => 'Entretien et sécurité des bâtiments et infrastructures publiques',
            'email'       => 'contact@batiments-publics.sn',
            'is_active'   => true,
        ],
    ];

    public function run(): void
    {
        foreach ($this->mairieRegions as $name => $region) {
            Authority::where('name', $name)->update(['region_name' => $region]);
        }

        foreach ($this->newAuthorities as $data) {
            Authority::firstOrCreate(['name' => $data['name']], $data);
        }

        $this->command?->info(
            'Autorités : régions des mairies renseignées, autorités spécialisées créées/vérifiées.'
        );
    }
}
