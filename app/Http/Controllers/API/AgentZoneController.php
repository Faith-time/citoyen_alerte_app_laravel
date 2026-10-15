<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\AgentZone;
use App\Models\User;
use App\Models\Authority;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgentZoneController extends Controller
{
    /**
     * Liste des zones d'agents
     */
    public function index(Request $request)
    {
        $user  = $request->user();
        $query = AgentZone::with('agent');

        // Si l'utilisateur est une autorité, ne montrer que SES agents
        if ($user->role === 'authority' && $user->authority_id) {
            $query->whereHas('agent', fn($q) =>
            $q->where('authority_id', $user->authority_id)
            );
        }

        return response()->json(['success' => true, 'data' => $query->get()]);
    }
    /**
     * Créer une zone d'agent
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'agent_id'     => 'required|exists:users,id',
            'authority_id' => 'nullable|exists:authorities,id',
            'commune_name' => 'nullable|string|max:100',
            'region_name'  => 'nullable|string|max:100',
            'radius_km'    => 'nullable|numeric|min:0.1|max:200',
            'center_lat'   => 'nullable|numeric|between:-90,90',
            'center_lng'   => 'nullable|numeric|between:-180,180',
            'is_primary'   => 'boolean',
        ]);

        if (!empty($validated['is_primary'])) {
            AgentZone::where('agent_id', $validated['agent_id'])
                ->update(['is_primary' => false]);
        }

        $zone = AgentZone::create($validated);
        $zone->load(['agent', 'authority']);

        return response()->json([
            'success' => true,
            'message' => 'Zone créée avec succès',
            'data'    => $zone,
        ], 201);
    }

    /**
     * Détail d'une zone
     */
    public function show(AgentZone $agentZone): JsonResponse
    {
        $agentZone->load(['agent', 'authority']);

        return response()->json(['success' => true, 'data' => $agentZone]);
    }

    /**
     * Mettre à jour une zone
     */
    public function update(Request $request, AgentZone $agentZone): JsonResponse
    {
        $validated = $request->validate([
            'authority_id' => 'nullable|exists:authorities,id',
            'commune_name' => 'nullable|string|max:100',
            'region_name'  => 'nullable|string|max:100',
            'radius_km'    => 'nullable|numeric|min:0.1|max:200',
            'center_lat'   => 'nullable|numeric|between:-90,90',
            'center_lng'   => 'nullable|numeric|between:-180,180',
            'is_primary'   => 'boolean',
        ]);

        // Retirer le statut primaire des autres zones si nécessaire
        if (!empty($validated['is_primary'])) {
            AgentZone::where('agent_id', $agentZone->agent_id)
                ->where('id', '!=', $agentZone->id)
                ->update(['is_primary' => false]);
        }

        $agentZone->update($validated);
        $agentZone->load(['agent', 'authority']);

        return response()->json([
            'success' => true,
            'message' => 'Zone mise à jour',
            'data'    => $agentZone,
        ]);
    }

    /**
     * Supprimer une zone
     */
    public function destroy(AgentZone $agentZone): JsonResponse
    {
        $agentZone->delete();

        return response()->json([
            'success' => true,
            'message' => 'Zone supprimée',
        ]);
    }

    // =========================================================================
    //  SECTION 2 — ACTIONS MÉTIER
    // =========================================================================

    /**
     * Définir une zone comme primaire pour un agent
     * PUT /agent-zones/{id}/set-primary
     */
    public function setPrimary(AgentZone $agentZone): JsonResponse
    {
        AgentZone::where('agent_id', $agentZone->agent_id)
            ->where('id', '!=', $agentZone->id)
            ->update(['is_primary' => false]);

        $agentZone->update(['is_primary' => true]);
        $agentZone->load(['agent', 'authority']);

        return response()->json([
            'success' => true,
            'message' => 'Zone primaire définie',
            'data'    => $agentZone,
        ]);
    }

    /**
     * Toutes les zones d'un agent
     * GET /agent-zones/agent/{agentId}
     */
    public function getByAgent(int $agentId): JsonResponse
    {
        $agent = User::find($agentId);

        if (!$agent) {
            return response()->json(['success' => false, 'message' => 'Agent non trouvé'], 404);
        }

        $zones = AgentZone::where('agent_id', $agentId)
            ->with('authority')
            ->get();

        return response()->json(['success' => true, 'data' => $zones]);
    }

    /**
     * Toutes les zones d'une autorité
     * GET /agent-zones/authority/{authorityId}
     */
    public function getByAuthority(int $authorityId): JsonResponse
    {
        $authority = Authority::find($authorityId);

        if (!$authority) {
            return response()->json(['success' => false, 'message' => 'Autorité non trouvée'], 404);
        }

        $zones = AgentZone::where('authority_id', $authorityId)
            ->with('agent')
            ->get();

        return response()->json([
            'success'   => true,
            'authority' => $authority->only(['id', 'name', 'type']),
            'data'      => $zones,
        ]);
    }

    // =========================================================================
    //  SECTION 3 — AFFECTATION INTELLIGENTE
    // =========================================================================

    /**
     * Trouver les agents disponibles pour un signalement (par GPS)
     * POST /agent-zones/suggest
     * Body: { "latitude": 14.71, "longitude": -17.46, "authority_id": 1 }
     */
    public function suggest(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'latitude'     => 'required|numeric|between:-90,90',
            'longitude'    => 'required|numeric|between:-180,180',
            'authority_id' => 'nullable|exists:authorities,id',
            'radius_km'    => 'nullable|numeric|min:1|max:100',
        ]);

        $lat       = $validated['latitude'];
        $lng       = $validated['longitude'];
        $searchKm  = $validated['radius_km'] ?? 15;

        $query = AgentZone::with(['agent', 'authority'])
            ->nearPoint($lat, $lng, $searchKm);

        if (!empty($validated['authority_id'])) {
            $query->where('authority_id', $validated['authority_id']);
        }

        $zones = $query->get()->map(function ($zone) {
            return [
                'zone_id'       => $zone->id,
                'distance_km'   => round($zone->distance_km, 2),
                'is_primary'    => $zone->is_primary,
                'commune_name'  => $zone->commune_name,
                'region_name'   => $zone->region_name,
                'radius_km'     => $zone->radius_km,
                'authority'     => $zone->authority?->only(['id', 'name', 'type']),
                'agent'         => $zone->agent ? [
                    'id'    => $zone->agent->id,
                    'name'  => $zone->agent->name,
                    'email' => $zone->agent->email,
                    'phone' => $zone->agent->phone ?? null,
                ] : null,
            ];
        });

        return response()->json([
            'success'    => true,
            'search_lat' => $lat,
            'search_lng' => $lng,
            'radius_km'  => $searchKm,
            'count'      => $zones->count(),
            'data'       => $zones,
        ]);
    }

    /**
     * Zones proches d'un point GPS (sans filtre autorité)
     * GET /agent-zones/nearby?latitude=14.71&longitude=-17.46&radius=10
     */
    public function nearby(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'latitude'  => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius'    => 'nullable|numeric|min:1|max:100',
        ]);

        $zones = AgentZone::with(['agent', 'authority'])
            ->nearPoint($validated['latitude'], $validated['longitude'], $validated['radius'] ?? 10)
            ->get();

        return response()->json(['success' => true, 'data' => $zones]);
    }
}
