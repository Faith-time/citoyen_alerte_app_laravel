<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * AuthorityProductionSeeder
 *
 * Tous les numéros, emails et sites web sont vérifiés sur les sources officielles :
 *  - senegalservices.sn (portail officiel de l'administration sénégalaise)
 *  - servicepublic.gouv.sn
 *  - Sites web officiels de chaque organisme
 *  - Comptes officiels réseaux sociaux
 *
 * ⚠️  Ce seeder utilise un champ `website`. Assurez-vous que votre migration
 *      inclut : $table->string('website')->nullable();
 */
class AuthorityProductionSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. Autorités sénégalaises ─────────────────────────────────────

        $authorities = [

            // ──────────────────────────────────────────────────────────────
            // AGEROUTE
            // Source : ageroute.sn/contacts + senegalservices.sn
            // ──────────────────────────────────────────────────────────────
            [
                'name'        => 'AGEROUTE',
                'type'        => 'government',
                'description' => 'Agence des Travaux et de Gestion des Routes – voirie, routes nationales et ouvrages d\'art.',
                'email'       => 'ageroute@ageroute.sn',
                'phone'       => '+221 33 869 07 51',
                'website'     => 'https://ageroute.sn',
                'is_active'   => true,
            ],

            // ──────────────────────────────────────────────────────────────
            // SENELEC
            // Source : senelec.sn/contact
            // Standard : 33 839 30 30 | Clientèle : 33 867 66 66
            // ──────────────────────────────────────────────────────────────
            [
                'name'        => 'SENELEC',
                'type'        => 'government',
                'description' => 'Société Nationale d\'Électricité – éclairage public et réseau électrique. Service clientèle : +221 33 867 66 66.',
                'email'       => 'contact@senelec.sn',
                'phone'       => '+221 33 839 30 30',
                'website'     => 'https://www.senelec.sn',
                'is_active'   => true,
            ],

            // ──────────────────────────────────────────────────────────────
            // UCG
            // Source : ucg.gouv.sn + au-senegal.com
            // Allo Déchets (numéro court citoyen) : 202 123
            // ──────────────────────────────────────────────────────────────
            [
                'name'        => 'UCG',
                'type'        => 'government',
                'description' => 'Unité de Coordination de la Gestion des Déchets Solides. Signalement citoyen via Allo Déchets : 202 123.',
                'email'       => 'contact@ucg.gouv.sn',
                'phone'       => '+221 33 823 39 69',
                'website'     => 'https://ucg.gouv.sn',
                'is_active'   => true,
            ],

            // ──────────────────────────────────────────────────────────────
            // ONAS
            // Source : onas.sn/nous-ecrire + senegalservices.sn + Twitter @OnasSenegal
            // Siège Hann : +221 33 859 35 35 | Numéro vert réclamations : 81 800 10 12
            // ──────────────────────────────────────────────────────────────
            [
                'name'        => 'ONAS',
                'type'        => 'government',
                'description' => 'Office National de l\'Assainissement du Sénégal – eaux usées et inondations. Numéro vert réclamations (gratuit) : 81 800 10 12.',
                'email'       => 'onas@onas.sn',
                'phone'       => '+221 33 859 35 35',
                'website'     => 'https://www.onas.sn',
                'is_active'   => true,
            ],

            // ──────────────────────────────────────────────────────────────
            // SEN'EAU (ex-SDE)
            // Source : seneau.sn/contacts + pseau.org + @sen_eau Twitter
            // Standard urgences 24h/24 : +221 33 839 37 37
            // Numéro vert gratuit       : 800 00 11 11
            // WhatsApp                  : +221 78 935 51 51
            // ──────────────────────────────────────────────────────────────
            [
                'name'        => 'SEN\'EAU',
                'type'        => 'government',
                'description' => 'Sen\'Eau – distribution d\'eau potable. Urgences 24h/24 : +221 33 839 37 37. Numéro vert gratuit : 800 00 11 11. WhatsApp : +221 78 935 51 51.',
                'email'       => 'eau@seneau.sn',
                'phone'       => '+221 33 839 37 37',
                'website'     => 'https://www.seneau.sn',
                'is_active'   => true,
            ],

            // ──────────────────────────────────────────────────────────────
            // Police Nationale
            // Urgence nationale : 17
            // Source : servicepublic.gouv.sn + interieur.gouv.sn
            // ──────────────────────────────────────────────────────────────
            [
                'name'        => 'Police Nationale',
                'type'        => 'government',
                'description' => 'Direction Générale de la Police Nationale – sécurité publique et ordre. Numéro d\'urgence national : 17.',
                'email'       => 'brppolice@interieur.gouv.sn',
                'phone'       => '17',
                'website'     => 'https://www.interieur.gouv.sn',
                'is_active'   => true,
            ],

            // ──────────────────────────────────────────────────────────────
            // Brigade Nationale des Sapeurs-Pompiers
            // Urgence nationale : 18
            // ──────────────────────────────────────────────────────────────
            [
                'name'        => 'Brigade des Sapeurs-Pompiers',
                'type'        => 'government',
                'description' => 'Brigade Nationale des Sapeurs-Pompiers – incendie, secours et accidents graves. Numéro d\'urgence national : 18.',
                'email'       => null,
                'phone'       => '18',
                'website'     => null,
                'is_active'   => true,
            ],

            // ──────────────────────────────────────────────────────────────
            // Gendarmerie Nationale
            // Urgence nationale : 30 (brigade la plus proche)
            // ──────────────────────────────────────────────────────────────
            [
                'name'        => 'Gendarmerie Nationale',
                'type'        => 'government',
                'description' => 'Gendarmerie Nationale – sécurité et ordre public en zone périurbaine et rurale. Numéro d\'urgence national : 30.',
                'email'       => null,
                'phone'       => '30',
                'website'     => null,
                'is_active'   => true,
            ],

            // ──────────────────────────────────────────────────────────────
            // Mairie de Dakar (Ville de Dakar)
            // Source : servicepublic.gouv.sn (bo.senegalservices.sn)
            // Tél. : 33 849 09 09 / 33 849 09 10 | Fax : 33 821 37 35
            // Email officiel maire : lemaire@villededakar.org
            // Site officiel        : villededakar.org
            // ──────────────────────────────────────────────────────────────
            [
                'name'        => 'Mairie de Dakar',
                'type'        => 'municipality',
                'description' => 'Ville de Dakar – bâtiments publics communaux, voirie et infrastructure locale. Bd Djily Mbaye x Robert Delmas.',
                'email'       => 'lemaire@villededakar.org',
                'phone'       => '+221 33 849 09 09',
                'website'     => 'https://www.villededakar.org',
                'is_active'   => true,
            ],

            // ──────────────────────────────────────────────────────────────
            // DEEC
            // Source : denv.gouv.sn/contact-us + servicepublic.gouv.sn + senegalservices.sn
            // 106 rue Carnot, BP 6557, Dakar
            // Tél. : 33 822 62 11 / 33 821 07 25 | Fax : 33 822 62 12
            // Email officiel site : contact@denv.gouv.sn
            // ──────────────────────────────────────────────────────────────
            [
                'name'        => 'DEEC',
                'type'        => 'government',
                'description' => 'Direction de l\'Environnement et des Établissements Classés – pollution, nuisances et contrôle environnemental. 106 rue Carnot, Dakar.',
                'email'       => 'contact@denv.gouv.sn',
                'phone'       => '+221 33 822 62 11',
                'website'     => 'https://www.denv.gouv.sn',
                'is_active'   => true,
            ],
        ];

        DB::table('authorities')->insertOrIgnore($authorities);

        // ── 2. Mapping Autorité ↔ Catégories ──────────────────────────────

        $getAuth = fn($name) => DB::table('authorities')->where('name', $name)->value('id');
        $getCat  = fn($name) => DB::table('categories')->where('name', $name)->value('id');

        $mappings = [
            // AGEROUTE → Voirie
            ['authority' => 'AGEROUTE', 'category' => 'Voirie'],
            ['authority' => 'AGEROUTE', 'category' => 'Nids de poule'],
            ['authority' => 'AGEROUTE', 'category' => 'Trottoir dégradé'],
            ['authority' => 'AGEROUTE', 'category' => 'Signalisation manquante'],
            ['authority' => 'AGEROUTE', 'category' => 'Route inondée'],
            ['authority' => 'AGEROUTE', 'category' => 'Dos d\'âne dangereux'],

            // SENELEC → Éclairage
            ['authority' => 'SENELEC', 'category' => 'Éclairage'],
            ['authority' => 'SENELEC', 'category' => 'Lampadaire en panne'],
            ['authority' => 'SENELEC', 'category' => 'Câble électrique dangereux'],
            ['authority' => 'SENELEC', 'category' => 'Zone non éclairée'],

            // UCG → Déchets
            ['authority' => 'UCG', 'category' => 'Déchets'],
            ['authority' => 'UCG', 'category' => 'Dépôt sauvage'],
            ['authority' => 'UCG', 'category' => 'Bac à ordures plein'],
            ['authority' => 'UCG', 'category' => 'Décharge illégale'],
            ['authority' => 'UCG', 'category' => 'Brûlage de déchets'],

            // ONAS → Assainissement / inondations
            ['authority' => 'ONAS', 'category' => 'Eau'],
            ['authority' => 'ONAS', 'category' => 'Inondation'],
            ['authority' => 'ONAS', 'category' => 'Eau stagnante'],
            ['authority' => 'ONAS', 'category' => 'Canalisation bouchée'],

            // SEN'EAU → Fuites eau potable
            ['authority' => 'SEN\'EAU', 'category' => 'Fuite d\'eau'],

            // Police → Sécurité urbaine
            ['authority' => 'Police Nationale', 'category' => 'Sécurité'],
            ['authority' => 'Police Nationale', 'category' => 'Vandalisme'],
            ['authority' => 'Police Nationale', 'category' => 'Accident de la route'],
            ['authority' => 'Police Nationale', 'category' => 'Lieu abandonné dangereux'],
            ['authority' => 'Police Nationale', 'category' => 'Éclairage insuffisant (danger)'],

            // Pompiers → Câbles et incendie
            ['authority' => 'Brigade des Sapeurs-Pompiers', 'category' => 'Câble électrique dangereux'],

            // Mairie → Bâtiments publics
            ['authority' => 'Mairie de Dakar', 'category' => 'Bâtiments'],
            ['authority' => 'Mairie de Dakar', 'category' => 'Bâtiment public dégradé'],

            // DEEC → Environnement / pollution
            ['authority' => 'DEEC', 'category' => 'Environnement'],
        ];

        foreach ($mappings as $m) {
            $authId = $getAuth($m['authority']);
            $catId  = $getCat($m['category']);

            if ($authId && $catId) {
                DB::table('authority_categories')->insertOrIgnore([
                    'authority_id' => $authId,
                    'category_id'  => $catId,
                    'is_primary'   => true,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);
            }
        }
    }
}
