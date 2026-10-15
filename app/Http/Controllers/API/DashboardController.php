<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\ReportRouting;
use App\Models\User;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    /**
     * Dashboard Citoyen
     */
    public function citizenDashboard(Request $request)
    {
        $user = $request->user();

        $stats = [
            'total_reports'   => Report::where('citizen_id', $user->id)->count(),
            'pending_reports' => Report::where('citizen_id', $user->id)
                ->where('status', 'pending')->count(),
            'resolved_reports'=> Report::where('citizen_id', $user->id)
                ->where('status', 'resolved')->count(),
            'recent_reports'  => Report::where('citizen_id', $user->id)
                ->with(['category'])
                ->latest()
                ->limit(5)
                ->get(),
        ];

        return response()->json(['success' => true, 'data' => $stats]);
    }

    /**
     * Dashboard Agent
     * Les assignations passent par report_routing → authority_id
     * et agents_zones lie agent_id ↔ authority_id
     */
    public function agentDashboard(Request $request)
    {
        $user = $request->user();

        // ── Autorité parente de l'agent (créé par une autorité) ──────────────────
        $parentAuthority = $user->authority_id
            ? \App\Models\Authority::find($user->authority_id)
            : null;

        // ── Autorités auxquelles cet agent est rattaché via agent_zones ──────────
        $authorityIds = DB::table('agents_zones')
            ->where('agent_id', $user->id)
            ->pluck('authority_id');

        // Si l'agent a une autorité parente et qu'elle n'est pas déjà dans la liste
        if ($parentAuthority && !$authorityIds->contains($parentAuthority->id)) {
            $authorityIds->push($parentAuthority->id);
        }

        // ── IDs des signalements routés vers ces autorités ───────────────────────
        $routedReportIds = DB::table('report_routing')
            ->whereIn('authority_id', $authorityIds)
            ->pluck('report_id');

        // ── Statistiques ─────────────────────────────────────────────────────────

        // Signalements actifs (routed ou in_progress)
        $assignedReports = Report::whereIn('id', $routedReportIds)
            ->whereIn('status', ['routed', 'in_progress'])
            ->count();

        // En attente (routed, pas encore pris en charge)
        $pendingReports = Report::whereIn('id', $routedReportIds)
            ->where('status', 'routed')
            ->count();

        // En cours
        $inProgress = Report::whereIn('id', $routedReportIds)
            ->where('status', 'in_progress')
            ->count();

        $completedToday = DB::table('interventions')
            ->where('agent_id', $user->id)
            ->where('status', 'completed')
            ->where(function ($q) {
                $q->whereDate('completed_at', today())
                    ->orWhere(function ($q2) {
                        $q2->whereNull('completed_at')
                            ->whereDate('updated_at', today());
                    });
            })
            ->count();

        // Interventions personnelles de cet agent
        $totalInterventions = DB::table('interventions')
            ->where('agent_id', $user->id)
            ->count();

        // ── 10 signalements récents ───────────────────────────────────────────────
        $recentAssignmentIds = DB::table('report_routing')
            ->whereIn('authority_id', $authorityIds)
            ->orderByDesc('routed_at')
            ->limit(10)
            ->pluck('report_id');

        $recentAssignments = Report::whereIn('id', $recentAssignmentIds)
            ->with(['category'])
            ->get()
            ->map(fn($r) => [
                'id'           => $r->id,
                'reference'    => $r->reference,
                'title'        => $r->title,
                'status'       => $r->status,
                'severity'     => $r->severity,
                'commune_name' => $r->commune_name,
                'region_name'  => $r->region_name,
                'category'     => $r->category ? ['name' => $r->category->name] : null,
                'created_at'   => $r->created_at,
            ]);

        return response()->json([
            'success' => true,
            'data'    => [
                // ── Infos autorité parente ────────────────────────────────────
                'authority_id'        => $parentAuthority?->id,
                'authority_name'      => $parentAuthority?->name,
                'authority_type'      => $parentAuthority?->type,

                // ── Stats ─────────────────────────────────────────────────────
                'assigned_reports'    => $assignedReports,
                'pending_reports'     => $pendingReports,
                'in_progress'         => $inProgress,
                'completed_today'     => $completedToday,
                'total_interventions' => $totalInterventions,

                // ── Signalements récents ──────────────────────────────────────
                'recent_assignments'  => $recentAssignments,
            ],
        ]);
    }
    /**
     * Dashboard Admin
     */
    public function adminDashboard(Request $request)
    {
        $period   = $request->get('period', 'month');
        $dateFrom = match ($period) {
            'day'  => now()->startOfDay(),
            'week' => now()->startOfWeek(),
            'year' => now()->startOfYear(),
            default => now()->startOfMonth(),
        };

        // Statuts par colonne
        $byStatusRaw = Report::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $byStatus = [
            'total'       => Report::count(),
            'pending'     => (int)($byStatusRaw['pending']     ?? 0),
            'routed'      => (int)($byStatusRaw['routed']      ?? 0),
            'in_progress' => (int)($byStatusRaw['in_progress'] ?? 0),
            'resolved'    => (int)($byStatusRaw['resolved']    ?? 0),
        ];

        $bySeverityRaw = Report::selectRaw('severity, COUNT(*) as count')
            ->groupBy('severity')
            ->pluck('count', 'severity');

        $bySeverity = [
            'low'    => (int)($bySeverityRaw['low']    ?? 0),
            'medium' => (int)($bySeverityRaw['medium'] ?? 0),
            'high'   => (int)($bySeverityRaw['high']   ?? 0),
        ];

        // Top catégories
        $byCategory = Report::select('categories.name', DB::raw('COUNT(reports.id) as count'))
            ->join('categories', 'reports.category_id', '=', 'categories.id')
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->map(fn($r) => ['name' => $r->name, 'count' => (int)$r->count]);

        // Performance agents via agents_zones + report_routing
        $agentPerformance = User::where('role', 'agent')
            ->get()
            ->map(function ($agent) {
                $authorityIds = DB::table('agents_zones')
                    ->where('agent_id', $agent->id)
                    ->pluck('authority_id');

                $reportIds = DB::table('report_routing')
                    ->whereIn('authority_id', $authorityIds)
                    ->pluck('report_id');

                $total    = $reportIds->count();
                $resolved = Report::whereIn('id', $reportIds)
                    ->where('status', 'resolved')
                    ->count();

                return [
                    'id'              => $agent->id,
                    'name'            => $agent->name,
                    'total_assigned'  => $total,
                    'total_resolved'  => $resolved,
                    'resolution_rate' => $total > 0 ? round($resolved / $total * 100, 1) : 0,
                ];
            })
            ->sortByDesc('total_resolved')
            ->values();

        $stats = [
            'overview' => [
                'total_users'    => User::count(),
                'total_citizens' => User::where('role', 'citizen')->count(),
                'total_agents'   => User::where('role', 'agent')->count(),
                'total_reports'  => Report::count(),
            ],
            'reports_by_status'   => $byStatus,
            'reports_by_severity' => $bySeverity,
            'reports_by_category' => $byCategory,
            'recent_period' => [
                'new_reports' => Report::where('created_at', '>=', $dateFrom)->count(),
                'resolved'    => Report::where('resolved_at', '>=', $dateFrom)
                    ->where('status', 'resolved')->count(),
                'new_users'   => User::where('created_at', '>=', $dateFrom)->count(),
            ],
            'agent_performance' => $agentPerformance,
            'flagged_reports'   => 0,
            'pending_appeals'   => 0,
        ];

        return response()->json(['success' => true, 'data' => $stats]);
    }

    // ── Autres endpoints (inchangés) ──────────────────────────────────────────

    public function reportsStatistics(Request $request)
    {
        $startDate = $request->get('start_date', now()->subMonth());
        $endDate   = $request->get('end_date', now());

        return response()->json(['success' => true, 'data' => [
            'total'    => Report::whereBetween('created_at', [$startDate, $endDate])->count(),
            'by_status'=> Report::whereBetween('created_at', [$startDate, $endDate])
                ->selectRaw('status, COUNT(*) as count')->groupBy('status')->get(),
        ]]);
    }

    public function categoryStatistics()
    {
        $stats = Category::withCount('reports')
            ->having('reports_count', '>', 0)
            ->orderByDesc('reports_count')
            ->get()
            ->map(fn($c) => [
                'id'            => $c->id,
                'name'          => $c->name,
                'total_reports' => $c->reports_count,
            ]);

        return response()->json(['success' => true, 'data' => $stats]);
    }

    public function geographicalStatistics()
    {
        $byCommune = Report::selectRaw('commune_name, COUNT(*) as count')
            ->whereNotNull('commune_name')
            ->groupBy('commune_name')
            ->orderByDesc('count')
            ->limit(20)
            ->get();

        $byRegion = Report::selectRaw('region_name, COUNT(*) as count')
            ->whereNotNull('region_name')
            ->groupBy('region_name')
            ->orderByDesc('count')
            ->get();

        return response()->json(['success' => true, 'data' => [
            'by_commune' => $byCommune,
            'by_region'  => $byRegion,
        ]]);
    }

    public function userStatistics()
    {
        return response()->json(['success' => true, 'data' => [
            'total_users'    => User::count(),
            'total_citizens' => User::where('role', 'citizen')->count(),
            'total_agents'   => User::where('role', 'agent')->count(),
            'total_admins'   => User::where('role', 'admin')->count(),
            'new_this_month' => User::whereMonth('created_at', now()->month)->count(),
        ]]);
    }

    public function realTimeStatistics()
    {
        return response()->json(['success' => true, 'data' => [
            'reports_last_hour' => Report::where('created_at', '>=', now()->subHour())->count(),
            'reports_today'     => Report::whereDate('created_at', today())->count(),
            'pending_reports'   => Report::where('status', 'pending')->count(),
        ]]);
    }

    public function exportStatistics(Request $request)
    {
        return response()->json(['success' => true, 'message' => 'Export coming soon']);
    }


    public function authorityDashboard(Request $request)
    {
        $user      = $request->user();
        $authority = \App\Models\Authority::find($user->authority_id);

        if (!$authority) {
            return response()->json([
                'success' => false,
                'message' => 'Aucune autorité liée à ce compte',
            ], 403);
        }

        $authorityId = $authority->id;
        $isMairie    = $authority->type === 'municipal';

        // IDs des reports routés vers cette autorité
        $routedReportIds = DB::table('report_routing')
            ->where('authority_id', $authorityId)
            ->pluck('report_id');

        // Extraire la région depuis le nom de la mairie (ex: "Mairie de Thiès" → "Thiès")
        $region = null;
        if ($isMairie) {
            preg_match('/Mairie de (.+)/i', $authority->name, $matches);
            $region = $matches[1] ?? null;
        }

        // Autorités disponibles pour routage secondaire (mairie uniquement)
        $availableAuthorities = [];
        if ($isMairie) {
            $pendingCategoryIds = Report::whereIn('id', $routedReportIds)
                ->whereIn('status', ['pending', 'acknowledged', 'routed'])
                ->pluck('category_id')
                ->unique();

            $recommendedAuthorityIds = DB::table('authority_categories')
                ->whereIn('category_id', $pendingCategoryIds)
                ->pluck('authority_id')
                ->unique();

            $availableAuthorities = \App\Models\Authority::where('type', 'government')
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
                ->map(fn($a) => [
                    'id'          => $a->id,
                    'name'        => $a->name,
                    'type'        => $a->type,
                    'recommended' => $recommendedAuthorityIds->contains($a->id),
                ])
                ->sortByDesc('recommended')
                ->values();
        }

        // Stats depuis report_routing
        $baseRoutingQuery = DB::table('report_routing')
            ->where('authority_id', $authorityId);

        $routingStatusCounts = DB::table('report_routing')
            ->where('authority_id', $authorityId)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        // Signalements récents avec structure attendue par le Flutter (report + routing imbriqués)
        $recentRoutings = ReportRouting::where('authority_id', $authority->id)
            ->with(['report' => function($q) {
                $q->select('id', 'title', 'reference', 'severity', 'status', 'category_id')
                    ->with('category:id,name');
            }])
            ->latest()
            ->take(5) // ← seulement 5 pour le dashboard home
            ->get();
        $stats = [
            // ── Identité ────────────────────────────────────────────────
            'authority_id'          => $authorityId,
            'authority_name'        => $authority->name,
            'authority_type'        => $authority->type,
            'is_mairie'             => $isMairie,
            'region'                => $region,
            'available_authorities' => $availableAuthorities,

            // ── Compteurs ───────────────────────────────────────────────
            'total_assigned' => (clone $baseRoutingQuery)->count(),
            'pending'        => (int) ($routingStatusCounts['pending']     ?? 0),
            'routed'         => (int) ($routingStatusCounts['routed']      ?? 0),
            'in_progress'    => (int) ($routingStatusCounts['in_progress'] ?? 0),
            'resolved'       => (int) ($routingStatusCounts['resolved']    ?? 0),
            'resolved_today' => DB::table('report_routing')
                ->where('authority_id', $authorityId)
                ->where('status', 'resolved')
                ->whereDate('resolved_at', today())
                ->count(),

            // ── Signalements récents ─────────────────────────────────────
            'recent_routings' => $recentRoutings,
        ];

        return response()->json(['success' => true, 'data' => $stats]);
    }
    // La mairie route un signalement vers une autorité spécialisée
    public function mairieRouteToAuthority(Request $request, $routingId)
    {
        $user    = $request->user();
        $routing = \App\Models\ReportRouting::findOrFail($routingId);

        // Vérifier que ce routing appartient bien à la mairie
        if ($routing->authority_id !== $user->authority_id) {
            return response()->json(['success' => false, 'message' => 'Non autorisé'], 403);
        }

        $newAuthorityId = $request->authority_id;

        // Créer un nouveau routing vers l'autorité compétente
        \App\Models\ReportRouting::create([
            'report_id'    => $routing->report_id,
            'authority_id' => $newAuthorityId,
            'status'       => 'routed',
            'routed_at'    => now(),
            'notes'        => 'Routé par ' . ($user->authority?->name ?? 'Mairie'),
        ]);

        // Marquer le routing de la mairie comme traité
        $routing->update(['status' => 'in_progress']);

        return response()->json(['success' => true, 'message' => 'Routé vers l\'autorité']);
    }

    public function mairieDashboard(Request $request): JsonResponse
    {
        $mairie = $request->user()->authority;

        if (!$mairie || $mairie->type !== 'municipal') {
            return response()->json(['success' => false, 'message' => 'Compte non rattaché à une mairie'], 403);
        }

        $base = \App\Models\Report::where('region_name', $mairie->region_name);

        $recent = (clone $base)->with(['category', 'routings.authority'])
            ->latest()->limit(10)->get()
            ->map(fn ($r) => [
                'id'           => $r->id,
                'title'        => $r->title,
                'status'       => $r->status,
                'severity'     => $r->severity,
                'commune_name' => $r->commune_name,
                'category'     => $r->category?->name,
                'authorities'  => $r->routings->pluck('authority.name')->filter()->values(),
                'created_at'   => $r->created_at,
            ]);

        return response()->json(['success' => true, 'data' => [
            'authority_name' => $mairie->name,
            'region'         => $mairie->region_name,
            'total'          => (clone $base)->count(),
            'pending'        => (clone $base)->where('status', 'pending')->count(),
            'routed'         => (clone $base)->where('status', 'routed')->count(),
            'in_progress'    => (clone $base)->where('status', 'in_progress')->count(),
            'resolved'       => (clone $base)->where('status', 'resolved')->count(),
            'recent_reports' => $recent,
        ]]);
    }
}
