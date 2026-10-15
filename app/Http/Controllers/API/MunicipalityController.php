<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Report;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MunicipalityController extends Controller
{
    /**
     * La mairie voit tous les signalements de sa région.
     */
    public function reports(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        if (empty($user->region_name)) {
            return response()->json([
                'success' => false,
                'message' => 'Aucune région associée à ce compte.',
            ], 403);
        }

        $query = Report::with(['category', 'images', 'routings.authority'])
            ->byRegion($user->region_name);

        // Filtres optionnels
        if ($request->has('status'))   $query->where('status', $request->status);
        if ($request->has('severity')) $query->bySeverity($request->severity);
        if ($request->has('commune'))  $query->byCommune($request->commune);
        if ($request->has('days'))     $query->recent((int) $request->days);

        $sortBy    = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $reports = $query->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'region'  => $user->region_name,
            'data'    => $reports,
        ]);
    }

    /**
     * Statistiques propres à la région de la mairie.
     */
    public function statistics(): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        if (empty($user->region_name)) {
            return response()->json(['success' => false, 'message' => 'Aucune région.'], 403);
        }

        $base = Report::byRegion($user->region_name);

        return response()->json([
            'success' => true,
            'region'  => $user->region_name,
            'data'    => [
                'total'       => (clone $base)->count(),
                'pending'     => (clone $base)->pending()->count(),
                'in_progress' => (clone $base)->inProgress()->count(),
                'resolved'    => (clone $base)->resolved()->count(),
                'by_severity' => [
                    'low'    => (clone $base)->bySeverity('low')->count(),
                    'medium' => (clone $base)->bySeverity('medium')->count(),
                    'high'   => (clone $base)->bySeverity('high')->count(),
                ],
                'by_commune' => Report::byRegion($user->region_name)
                    ->selectRaw('commune_name, COUNT(*) as count')
                    ->groupBy('commune_name')
                    ->orderByDesc('count')
                    ->get(),
                'recent_5' => (clone $base)
                    ->with(['category'])
                    ->latest()->limit(5)->get(),
            ],
        ]);
    }
}
