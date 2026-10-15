<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Jobs\EscalateUnacknowledgedRouting;
use App\Models\CategoryAuthorityMapping;
use App\Models\Report;
use App\Models\ReportImage;
use App\Models\ReportRouting;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;


class ReportController extends Controller
{
    /**
     * Liste des signalements
     */
    public function index(Request $request): JsonResponse
    {
        $query = Report::with(['citizen', 'category', 'images']);

        if ($request->has('authority_id')) {
            $authorityId = (int) $request->authority_id;
            $routedReportIds = DB::table('report_routing')
                ->where('authority_id', $authorityId)
                ->pluck('report_id');
            $query->whereIn('id', $routedReportIds);
        }

        if ($request->has('status')) {
            $status = $request->status;
            if ($status === 'pending') {
                $query->whereIn('status', ['pending', 'acknowledged']);
            } else {
                $query->where('status', $status);
            }
        }
        if ($request->has('severity')) {
            $query->bySeverity($request->severity);
        }
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        if ($request->has('commune')) {
            $query->byCommune($request->commune);
        }
        if ($request->has('region')) {
            $query->byRegion($request->region);
        }
        if ($request->has('days')) {
            $query->recent($request->days);
        }
        if ($request->boolean('anonymous_only')) {
            $query->anonymous();
        }
        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('reference', 'LIKE', '%' . $request->search . '%')
                    ->orWhere('title', 'LIKE', '%' . $request->search . '%');
            });
        }

        $sortBy    = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $reports = $query->paginate($request->get('per_page', 15));

        if ($request->has('authority_id')) {
            $authorityId = (int) $request->authority_id;

            $routingData = DB::table('report_routing')
                ->where('authority_id', $authorityId)
                ->select('report_id', 'id as routing_id', 'status', 'acknowledged_at')
                ->get()
                ->keyBy('report_id');

            $reports->getCollection()->transform(function ($report) use ($routingData) {
                $routing = $routingData[$report->id] ?? null;
                if ($routing) {
                    $report->status         = in_array($routing->status, ['pending', 'acknowledged'])
                        ? 'pending'
                        : $routing->status;
                    $report->routing_id     = $routing->routing_id;
                    $report->acknowledged_at = $routing->acknowledged_at;
                }
                return $report;
            });
        }

        return response()->json($reports);
    }

    /**
     * Créer un signalement.
     *
     * Le routage est désormais 100% automatique et direct : dès la
     * création, le signalement est envoyé à TOUTES les autorités
     * compétentes pour sa catégorie (une seule dans la plupart des cas,
     * plusieurs simultanément pour les catégories qui l'exigent, comme
     * "Accident de la route" — police + pompiers + SAMU en même temps).
     * Aucune mairie n'intervient dans ce circuit.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category_id'  => 'required|exists:categories,id',
            'title'        => 'required|string|max:255',
            'description'  => 'required|string',
            'latitude'     => 'required|numeric|between:-90,90',
            'longitude'    => 'required|numeric|between:-180,180',
            'address'      => 'nullable|string',
            'commune_name' => 'nullable|string',
            'region_name'  => 'nullable|string',
            'severity'     => 'required|in:low,medium,high',
            'is_anonymous' => 'boolean',
        ]);

        $validated['citizen_id'] = auth()->id();
        $validated['status']     = 'pending';

        $report = Report::create($validated);

        $routings = $this->autoRouteReport($report);

        // Diffusion immédiate à TOUTES les autorités routées (en parallèle,
        // pas en séquence) : chaque autorité voit le signalement dès sa
        // création, sans délai d'attente d'un intermédiaire.
        foreach ($routings as $routing) {
            app(\App\Services\FcmService::class)
                ->notifyAuthorityNewReport($routing->authority_id, $report->title);
        }

        // Pour un signalement urgent, on programme une vérification
        // d'accusé de réception 5 minutes plus tard, par autorité routée.
        // Si personne n'a accusé réception, une relance automatique part
        // (voir EscalateUnacknowledgedRouting), jusqu'à 3 fois.
        if ($report->severity === 'high') {
            foreach ($routings as $routing) {
                EscalateUnacknowledgedRouting::dispatch($routing->id)->delay(now()->addMinutes(5));
            }
        }

        $report->load(['citizen', 'category']);

        return response()->json([
            'message' => 'Signalement créé avec succès',
            'data'    => $report,
        ], 201);
    }

    /**
     * Route un signalement directement vers TOUTES les autorités
     * compétentes pour sa catégorie (selon CategoryAuthorityMapping),
     * en priorisant un mapping régional précis sur un mapping national
     * par défaut. Aucune mairie n'est jamais une cible de routage.
     *
     * Si aucune autorité n'est mappée pour la catégorie, le signalement
     * reste en 'pending' : un administrateur peut le router manuellement
     * via l'endpoint existant (routingStore, réservé au rôle admin).
     *
     * @return Collection<int, ReportRouting>
     */
    private function autoRouteReport(Report $report): Collection
    {
        $mappings = CategoryAuthorityMapping::where('category_id', $report->category_id)
            ->where(function ($q) use ($report) {
                $q->where('region_name', $report->region_name)
                    ->orWhereNull('region_name');
            })
            ->orderByRaw('region_name IS NULL') // priorité au mapping régional précis
            ->get()
            ->unique('authority_id'); // une seule ligne par autorité (évite les doublons régional+national)

        if ($mappings->isEmpty()) {
            Log::warning('Signalement créé sans autorité mappée pour sa catégorie', [
                'report_id'   => $report->id,
                'category_id' => $report->category_id,
                'region_name' => $report->region_name,
            ]);
            return collect();
        }

        $createdRoutings = collect();

        foreach ($mappings as $mapping) {
            $routing = ReportRouting::firstOrCreate(
                ['report_id' => $report->id, 'authority_id' => $mapping->authority_id],
                [
                    'status'    => 'routed',
                    'routed_at' => now(),
                    'notes'     => 'Routage automatique par catégorie',
                ]
            );
            $createdRoutings->push($routing);
        }

        $report->update(['status' => 'routed']);

        return $createdRoutings;
    }

    /**
     * Détail d'un signalement
     */
    public function show(Report $report): JsonResponse
    {
        $report->load(['citizen', 'category', 'images', 'routings.authority']);

        return response()->json($report);
    }

    /**
     * Mettre à jour un signalement
     */
    public function update(Request $request, Report $report): JsonResponse
    {
        if ($report->citizen_id !== auth()->id() && !auth()->user()->isAdmin()) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $validated = $request->validate([
            'category_id' => 'sometimes|exists:categories,id',
            'title'       => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'severity'    => 'sometimes|in:low,medium,high',
        ]);

        $report->update($validated);
        $report->load(['citizen', 'category']);

        return response()->json([
            'message' => 'Signalement mis à jour avec succès',
            'data'    => $report,
        ]);
    }

    /**
     * Supprimer un signalement
     */
    public function destroy(Report $report): JsonResponse
    {
        if ($report->citizen_id !== auth()->id() && !auth()->user()->isAdmin()) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $report->delete();

        return response()->json(['message' => 'Signalement supprimé avec succès']);
    }

    /**
     * Mettre à jour le statut d'un signalement
     */
    public function updateStatus(Request $request, Report $report): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,routed,in_progress,resolved',
        ]);

        $report->update($validated);

        if ($validated['status'] === 'resolved') {
            $report->update(['resolved_at' => now()]);
        }

        app(\App\Services\FcmService::class)
            ->notifyCitizenReportStatus($report->citizen_id, $report->id, $report->status_label);

        $this->checkAndBroadcastActuality($report);

        return response()->json([
            'message' => 'Statut mis à jour avec succès',
            'data'    => $report,
        ]);
    }

    /**
     * Marquer un signalement comme résolu
     */
    public function markAsResolved(Report $report): JsonResponse
    {
        $report->update([
            'status'      => 'resolved',
            'resolved_at' => now(),
        ]);

        app(\App\Services\FcmService::class)
            ->notifyCitizenReportStatus($report->citizen_id, $report->id, 'Résolu');

        $this->checkAndBroadcastActuality($report);

        return response()->json([
            'message' => 'Signalement marqué comme résolu',
            'data'    => $report,
        ]);
    }

    /**
     * Vérifie si un signalement résolu possède ses deux photos (before/initial + after)
     * et déclenche, le cas échéant, la diffusion "Actualités" à tous les citoyens.
     */
    private function checkAndBroadcastActuality(Report $report): void
    {
        if ($report->status !== 'resolved') {
            return;
        }

        $hasBoth = $report->images()->where('type', 'after')->exists()
            && ($report->images()->where('type', 'before')->exists()
                || $report->images()->where('type', 'initial')->exists());

        if ($hasBoth) {
            app(\App\Services\FcmService::class)
                ->notifyAllCitizensNewActuality($report->title);
        }
    }

    /**
     * Signalements à proximité géographique
     */
    public function nearby(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'latitude'  => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius'    => 'nullable|numeric|min:0.1|max:50',
        ]);

        $reports = Report::nearby(
            $validated['latitude'],
            $validated['longitude'],
            $validated['radius'] ?? 5
        )->with(['citizen', 'category', 'images'])->get();

        return response()->json($reports);
    }

    /**
     * Signalements d'un citoyen
     */
    public function userReports($userId): JsonResponse
    {
        $reports = Report::where('citizen_id', $userId)
            ->with(['category', 'images'])
            ->latest()
            ->paginate(15);

        return response()->json($reports);
    }

    /**
     * Tableau de bord
     */
    public function dashboard(): JsonResponse
    {
        return response()->json([
            'overview' => [
                'total'      => Report::count(),
                'today'      => Report::whereDate('created_at', today())->count(),
                'this_week'  => Report::where('created_at', '>=', now()->startOfWeek())->count(),
                'this_month' => Report::where('created_at', '>=', now()->startOfMonth())->count(),
            ],
            'by_status' => [
                'pending'     => Report::pending()->count(),
                'routed'      => Report::routed()->count(),
                'in_progress' => Report::inProgress()->count(),
                'resolved'    => Report::resolved()->count(),
            ],
            'urgent' => Report::bySeverity('high')
                ->whereIn('status', ['pending', 'routed'])
                ->with(['category', 'citizen'])
                ->latest()->limit(5)->get(),
            'recent' => Report::with(['category', 'citizen'])
                ->latest()->limit(10)->get(),
        ]);
    }

    /**
     * Statistiques des signalements
     */
    public function statistics(Request $request): JsonResponse
    {
        $days  = $request->get('days', 30);
        $total = Report::count();

        return response()->json([
            'total'       => $total,
            'pending'     => Report::pending()->count(),
            'routed'      => Report::routed()->count(),
            'in_progress' => Report::inProgress()->count(),
            'resolved'    => Report::resolved()->count(),
            'recent'      => Report::recent($days)->count(),
            'by_severity' => [
                'low'    => Report::bySeverity('low')->count(),
                'medium' => Report::bySeverity('medium')->count(),
                'high'   => Report::bySeverity('high')->count(),
            ],
            'by_category' => Report::selectRaw('category_id, COUNT(*) as count')
                ->groupBy('category_id')
                ->with('category:id,name')
                ->get()
                ->pluck('count', 'category.name'),
            'resolution_rate' => $total > 0
                ? round((Report::resolved()->count() / $total) * 100, 2)
                : 0,
        ]);
    }

    // ==========================================
    //  SECTION 2 — IMAGES (ReportImage)
    // ==========================================

    public function images(Report $report, Request $request): JsonResponse
    {
        $query = $report->images()->with('uploader');

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }
        if ($request->boolean('validated_only')) {
            $query->validated();
        }

        return response()->json($query->latest()->get());
    }

    public function imagesBeforeAfter(Report $report): JsonResponse
    {
        return response()->json([
            'before' => $report->images()->before()->get(),
            'after'  => $report->images()->after()->get(),
        ]);
    }

    public function imageStore(Request $request, Report $report): JsonResponse
    {
        $validated = $request->validate([
            'image' => 'required|file|mimes:jpeg,jpg,png,mp4,mov|max:51200',
            'type'  => 'required|in:initial,before,after,evidence',
        ]);

        $file    = $request->file('image');
        $isVideo = str_starts_with($file->getMimeType() ?? '', 'video/');

        $path = $file->store('report_images', 'public');

        $image = ReportImage::create([
            'report_id'   => $report->id,
            'image_path'  => $path,
            'type'        => $validated['type'],
            'media_type'  => $isVideo ? 'video' : 'image',
            'uploaded_by' => auth()->id(),
            'file_size'   => $file->getSize(),
            'mime_type'   => $file->getMimeType(),
        ]);

        $image->load(['report', 'uploader']);

        return response()->json([
            'message' => $isVideo ? 'Vidéo uploadée avec succès' : 'Image uploadée avec succès',
            'data'    => $image,
        ], 201);
    }

    public function imageStoreMultiple(Request $request, Report $report): JsonResponse
    {
        $validated = $request->validate([
            'images'   => 'required|array|min:1|max:5',
            'images.*' => 'file|mimes:jpeg,jpg,png,mp4,mov|max:51200',
            'type'     => 'required|in:initial,before,after,evidence',
        ]);

        $uploaded = [];

        foreach ($request->file('images') as $file) {
            $isVideo = str_starts_with($file->getMimeType() ?? '', 'video/');

            $path = $file->store('report_images', 'public');

            $uploaded[] = ReportImage::create([
                'report_id'   => $report->id,
                'image_path'  => $path,
                'type'        => $validated['type'],
                'media_type'  => $isVideo ? 'video' : 'image',
                'uploaded_by' => auth()->id(),
                'file_size'   => $file->getSize(),
                'mime_type'   => $file->getMimeType(),
            ]);
        }

        return response()->json([
            'message' => 'Médias uploadés avec succès',
            'data'    => $uploaded,
            'count'   => count($uploaded),
        ], 201);
    }

    public function imageDestroy(Report $report, ReportImage $image): JsonResponse
    {
        if ($image->uploaded_by !== auth()->id() && !auth()->user()->isAdmin()) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $image->delete();

        return response()->json(['message' => 'Image supprimée avec succès']);
    }

    public function imageValidate(Report $report, ReportImage $image): JsonResponse
    {
        $image->validate();

        return response()->json([
            'message' => 'Image validée avec succès',
            'data'    => $image,
        ]);
    }

    public function imageDownload(Report $report, ReportImage $image): mixed
    {
        if (!Storage::disk('public')->exists($image->image_path)) {
            return response()->json(['message' => 'Image non trouvée'], 404);
        }

        return Storage::disk('public')->download($image->image_path);
    }

    public function imageAnalyzeAI(Report $report, ReportImage $image): JsonResponse
    {
        $image->update([
            'ai_confidence_score' => 0.85,
            'ai_flags'            => ['quality_check' => 'good', 'content' => 'infrastructure'],
        ]);

        return response()->json([
            'message' => 'Image analysée avec succès',
            'data'    => $image,
        ]);
    }

    public function imageStatistics(): JsonResponse
    {
        return response()->json([
            'total'     => ReportImage::count(),
            'validated' => ReportImage::validated()->count(),
            'by_type'   => [
                'initial'  => ReportImage::initial()->count(),
                'before'   => ReportImage::before()->count(),
                'after'    => ReportImage::after()->count(),
                'evidence' => ReportImage::evidence()->count(),
            ],
            'total_size'         => ReportImage::sum('file_size'),
            'average_confidence' => ReportImage::whereNotNull('ai_confidence_score')
                ->avg('ai_confidence_score'),
        ]);
    }

    // ==========================================
    //  SECTION 3 — ROUTAGE (ReportRouting)
    // ==========================================

    public function routings(Report $report): JsonResponse
    {
        $routings = $report->routings()
            ->with('authority')
            ->latest('routed_at')
            ->get();

        return response()->json($routings);
    }

    /**
     * Router MANUELLEMENT un signalement vers une autorité — réservé à
     * l'admin, utilisé uniquement en filet de sécurité pour les cas où
     * autoRouteReport() n'a trouvé aucune correspondance (catégorie non
     * mappée), pas pour un usage courant.
     */
    public function routingStore(Request $request, Report $report): JsonResponse
    {
        $validated = $request->validate([
            'authority_id' => 'required|exists:authorities,id',
            'notes'        => 'nullable|string',
        ]);

        $exists = ReportRouting::where('report_id', $report->id)
            ->where('authority_id', $validated['authority_id'])
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Ce signalement est déjà routé vers cette autorité',
            ], 422);
        }

        $routing = ReportRouting::create([
            'report_id'    => $report->id,
            'authority_id' => $validated['authority_id'],
            'notes'        => $validated['notes'] ?? null,
            'status'       => 'routed',
        ]);

        $report->update(['status' => 'routed']);
        $routing->load(['report', 'authority']);

        return response()->json([
            'message' => 'Signalement routé avec succès',
            'data'    => $routing,
        ], 201);
    }

    public function routingUpdate(Request $request, Report $report, ReportRouting $routing): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'sometimes|in:routed,in_progress,resolved',
            'notes'  => 'nullable|string',
        ]);

        if (isset($validated['status']) && $validated['status'] === 'resolved') {
            $validated['resolved_at'] = now();

            $allResolved = ReportRouting::where('report_id', $report->id)
                    ->where('id', '!=', $routing->id)
                    ->where('status', '!=', 'resolved')
                    ->count() === 0;

            if ($allResolved) {
                $report->update([
                    'status'      => 'resolved',
                    'resolved_at' => now(),
                ]);
            }
        }

        $routing->update($validated);
        $routing->load(['report', 'authority']);

        return response()->json([
            'message' => 'Routage mis à jour avec succès',
            'data'    => $routing,
        ]);
    }

    public function routingDestroy(Report $report, ReportRouting $routing): JsonResponse
    {
        $routing->delete();

        if (!ReportRouting::where('report_id', $report->id)->exists()) {
            $report->update(['status' => 'pending']);
        }

        return response()->json(['message' => 'Routage supprimé avec succès']);
    }

    public function routingMarkInProgress(Report $report, ReportRouting $routing): JsonResponse
    {
        $routing->markAsInProgress();
        $report->update(['status' => 'in_progress']);

        return response()->json([
            'message' => 'Routage marqué comme en cours',
            'data'    => $routing,
        ]);
    }

    public function routingMarkResolved(Request $request, Report $report, ReportRouting $routing): JsonResponse
    {
        $validated = $request->validate([
            'notes' => 'nullable|string',
        ]);

        $routing->markAsResolved($validated['notes'] ?? null);

        app(\App\Services\FcmService::class)
            ->notifyCitizenReportStatus($report->citizen_id, $report->id, 'Résolu');

        $allResolved = ReportRouting::where('report_id', $report->id)
                ->where('status', '!=', 'resolved')
                ->count() === 0;

        if ($allResolved) {
            $report->update([
                'status'      => 'resolved',
                'resolved_at' => now(),
            ]);

            $this->checkAndBroadcastActuality($report);
        }

        return response()->json([
            'message' => 'Routage marqué comme résolu',
            'data'    => $routing,
        ]);
    }

    /**
     * L'autorité (ou un de ses agents) accuse réception d'un routage.
     * Stoppe l'escalade automatique programmée pour CE routage précis —
     * les autres autorités éventuellement co-routées sur le même
     * signalement urgent continuent leur propre cycle d'escalade tant
     * qu'elles n'ont pas accusé réception à leur tour.
     */
    public function routingAcknowledge(Request $request, Report $report, ReportRouting $routing): JsonResponse
    {
        $user = $request->user();

        if ($routing->report_id !== $report->id) {
            return response()->json(['success' => false, 'message' => 'Routage introuvable pour ce signalement'], 404);
        }

        $isAuthorized = $user->role === 'admin'
            || (in_array($user->role, ['authority', 'agent'], true) && $user->authority_id === $routing->authority_id);

        if (!$isAuthorized) {
            return response()->json(['success' => false, 'message' => 'Non autorisé'], 403);
        }

        if ($routing->acknowledged_at === null) {
            $routing->update([
                'acknowledged_at' => now(),
                'acknowledged_by' => $user->id,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Accusé de réception enregistré',
            'data'    => $routing,
        ]);
    }

    /**
     * Réassigner un routage vers une autre autorité (admin uniquement)
     */
    public function routingReassign(Request $request, Report $report, ReportRouting $routing): JsonResponse
    {
        $validated = $request->validate([
            'authority_id' => 'required|exists:authorities,id',
            'notes'        => 'nullable|string',
        ]);

        $routing->update([
            'authority_id'    => $validated['authority_id'],
            'status'          => 'routed',
            'notes'           => $validated['notes'] ?? $routing->notes,
            'routed_at'       => now(),
            'acknowledged_at' => null,
            'acknowledged_by' => null,
            'escalated_at'    => null,
            'escalation_level'=> 0,
        ]);

        $routing->load(['report', 'authority']);

        return response()->json([
            'message' => 'Routage réassigné avec succès',
            'data'    => $routing,
        ]);
    }

    public function routingsPendingForAuthority($authorityId): JsonResponse
    {
        $routings = ReportRouting::where('authority_id', $authorityId)
            ->pending()
            ->with(['report.citizen', 'report.category'])
            ->latest('routed_at')
            ->get();

        return response()->json($routings);
    }

    public function routingStatistics(Request $request): JsonResponse
    {
        $days = $request->get('days', 30);

        return response()->json([
            'total'       => ReportRouting::count(),
            'pending'     => ReportRouting::pending()->count(),
            'in_progress' => ReportRouting::inProgress()->count(),
            'resolved'    => ReportRouting::resolved()->count(),
            'recent'      => ReportRouting::recent($days)->count(),
            'by_authority' => ReportRouting::selectRaw('authority_id, COUNT(*) as count')
                ->groupBy('authority_id')
                ->with('authority:id,name')
                ->get()
                ->map(fn ($r) => [
                    'authority' => $r->authority,
                    'count'     => $r->count,
                ]),
            'average_resolution_time' => ReportRouting::resolved()
                ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, routed_at, resolved_at)) as avg_hours')
                ->value('avg_hours'),
        ]);
    }

    public function resolvedFeed(Request $request): JsonResponse
    {
        $reports = Report::resolved()
            ->with([
                'category:id,name',
                'images',
                'routings.authority:id,name',
            ])
            ->orderByDesc('resolved_at')
            ->get();

        if ($reports->isEmpty()) {
            return response()->json(['success' => true, 'data' => []]);
        }

        $interventions = \App\Models\Intervention::whereIn('report_id', $reports->pluck('id'))
            ->where('status', 'completed')
            ->latest('completed_at')
            ->get()
            ->unique('report_id')
            ->keyBy('report_id');

        $agentIds = $interventions->pluck('agent_id')->filter()->unique();
        $agents = \App\Models\User::whereIn('id', $agentIds)
            ->get(['id', 'name', 'avatar'])
            ->keyBy('id');

        $feed = $reports->map(function ($report) use ($interventions, $agents) {
            $before = $report->images->where('type', 'before')->sortBy('created_at')->first()
                ?? $report->images->where('type', 'initial')->sortBy('created_at')->first();

            $after = $report->images->where('type', 'after')->sortByDesc('created_at')->first();

            $intervention = $interventions->get($report->id);
            $agent        = $intervention ? $agents->get($intervention->agent_id) : null;

            $routing = $report->routings->firstWhere('status', 'resolved')
                ?? $report->routings->first();

            return [
                'id'             => $report->id,
                'title'          => $report->title,
                'category_name'  => $report->category?->name,
                'commune_name'   => $report->commune_name,
                'region_name'    => $report->region_name,
                'authority_name' => $routing?->authority?->name,
                'agent_name'     => $agent?->name,
                'agent_avatar'   => $agent?->avatar,
                'photo_before'      => $before ? asset('storage/' . $before->image_path) : null,
                'photo_before_type' => $before?->media_type ?? 'image',
                'photo_after'       => $after ? asset('storage/' . $after->image_path) : null,
                'photo_after_type'  => $after?->media_type ?? 'image',
                'resolved_at'    => $report->resolved_at,
            ];
        })
            ->filter(fn ($r) => $r['photo_after'])
            ->values();

        return response()->json(['success' => true, 'data' => $feed]);
    }
}
