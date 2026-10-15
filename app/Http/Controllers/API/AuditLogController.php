<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AuditLogController extends Controller
{
    /**
     * Afficher la liste des logs d'audit
     */
    public function index(Request $request): JsonResponse
    {
        $query = AuditLog::with('user');

        // Filtre par utilisateur
        if ($request->has('user_id')) {
            $query->forUser($request->user_id);
        }

        // Filtre par table
        if ($request->has('table_name')) {
            $query->forTable($request->table_name);
        }

        // Filtre par action
        if ($request->has('action')) {
            $query->where('action', $request->action);
        }

        // Filtre par période
        if ($request->has('days')) {
            $query->recent($request->days);
        }

        // Tri par date décroissante
        $query->orderBy('created_at', 'desc');

        $logs = $query->paginate($request->get('per_page', 20));

        return response()->json($logs);
    }

    /**
     * Afficher un log spécifique
     */
    public function show(AuditLog $auditLog): JsonResponse
    {
        $auditLog->load('user');

        return response()->json([
            'log' => $auditLog,
            'changes' => $auditLog->getChanges()
        ]);
    }

    /**
     * Obtenir les logs pour un enregistrement spécifique
     */
    public function getRecordLogs(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'table_name' => 'required|string',
            'record_id' => 'required|integer',
        ]);

        $logs = AuditLog::forRecord($validated['table_name'], $validated['record_id'])
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($logs);
    }

    /**
     * Obtenir les statistiques des logs
     */
    public function statistics(Request $request): JsonResponse
    {
        $days = $request->get('days', 30);

        $stats = [
            'total_actions' => AuditLog::recent($days)->count(),
            'actions_by_type' => AuditLog::recent($days)
                ->selectRaw('action, COUNT(*) as count')
                ->groupBy('action')
                ->pluck('count', 'action'),
            'actions_by_table' => AuditLog::recent($days)
                ->selectRaw('table_name, COUNT(*) as count')
                ->groupBy('table_name')
                ->orderByDesc('count')
                ->limit(10)
                ->pluck('count', 'table_name'),
            'most_active_users' => AuditLog::recent($days)
                ->selectRaw('user_id, COUNT(*) as count')
                ->groupBy('user_id')
                ->orderByDesc('count')
                ->limit(10)
                ->with('user:id,name')
                ->get()
                ->map(function ($log) {
                    return [
                        'user' => $log->user,
                        'actions_count' => $log->count
                    ];
                }),
        ];

        return response()->json($stats);
    }

    /**
     * Rechercher dans les logs
     */
    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'query' => 'required|string|min:2',
            'table_name' => 'nullable|string',
            'action' => 'nullable|string',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
        ]);

        $query = AuditLog::with('user');

        // Recherche dans les anciennes et nouvelles valeurs
        $query->where(function ($q) use ($validated) {
            $q->whereRaw('LOWER(old_values) LIKE ?', ['%' . strtolower($validated['query']) . '%'])
                ->orWhereRaw('LOWER(new_values) LIKE ?', ['%' . strtolower($validated['query']) . '%']);
        });

        // Filtres additionnels
        if (isset($validated['table_name'])) {
            $query->forTable($validated['table_name']);
        }

        if (isset($validated['action'])) {
            $query->where('action', $validated['action']);
        }

        if (isset($validated['date_from'])) {
            $query->where('created_at', '>=', $validated['date_from']);
        }

        if (isset($validated['date_to'])) {
            $query->where('created_at', '<=', $validated['date_to']);
        }

        $logs = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return response()->json($logs);
    }

    /**
     * Exporter les logs
     */
    public function export(Request $request): JsonResponse
    {
        // Cette méthode pourrait générer un fichier CSV ou Excel
        // Pour l'instant, on retourne juste les données

        $validated = $request->validate([
            'date_from' => 'required|date',
            'date_to' => 'required|date',
            'table_name' => 'nullable|string',
        ]);

        $query = AuditLog::with('user')
            ->whereBetween('created_at', [$validated['date_from'], $validated['date_to']]);

        if (isset($validated['table_name'])) {
            $query->forTable($validated['table_name']);
        }

        $logs = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'message' => 'Export généré avec succès',
            'count' => $logs->count(),
            'data' => $logs
        ]);
    }
}
