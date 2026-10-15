<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AgentZoneSeeder extends Seeder
{
    /**
     * Rayon par défaut (en km) pour les zones créées automatiquement.
     */
    protected const DEFAULT_RADIUS_KM = 5.0;

    public function run(): void
    {
        $agents = User::where('role', 'agent')
            ->whereNotNull('authority_id')
            ->get(['id', 'authority_id', 'name']);

        if ($agents->isEmpty()) {
            $this->command->warn("Aucun agent trouvé (role = 'agent' avec authority_id renseigné).");
            return;
        }

        // Agents qui ont déjà au moins une zone, pour ne pas dupliquer.
        $existingAgentIds = DB::table('agents_zones')
            ->pluck('agent_id')
            ->unique()
            ->all();

        $created = 0;
        $now = now();

        foreach ($agents as $agent) {
            if (in_array($agent->id, $existingAgentIds, true)) {
                continue;
            }

            DB::table('agents_zones')->insert([
                'agent_id'     => $agent->id,
                'authority_id' => $agent->authority_id,
                'commune_name' => null,
                'region_name'  => null,
                'radius_km'    => self::DEFAULT_RADIUS_KM,
                'center_lat'   => null,
                'center_lng'   => null,
                'is_primary'   => 1,
                'created_at'   => $now,
            ]);

            $created++;
        }

        $this->command->info("Terminé : $created zone(s) d'agent créée(s) sur " . $agents->count() . " agent(s) au total.");
    }
}
