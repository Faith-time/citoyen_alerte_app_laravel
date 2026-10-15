<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\AppVersion;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AppVersionController extends Controller
{
    /**
     * Afficher la liste des versions
     */
    public function index(Request $request): JsonResponse
    {
        $query = AppVersion::query();

        // Filtre par plateforme
        if ($request->has('platform')) {
            $query->forPlatform($request->platform);
        }

        // Versions actives uniquement
        if ($request->boolean('active_only')) {
            $query->active();
        }

        $versions = $query->latest()->paginate($request->get('per_page', 15));

        return response()->json($versions);
    }

    /**
     * Créer une nouvelle version
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'platform' => 'required|in:ios,android',
            'version' => 'required|string',
            'build_number' => 'required|integer',
            'is_mandatory' => 'boolean',
            'min_supported_version' => 'nullable|string',
            'release_notes' => 'nullable|string',
            'download_url' => 'nullable|url',
            'is_active' => 'boolean',
            'released_at' => 'nullable|date',
        ]);

        $appVersion = AppVersion::create($validated);

        return response()->json([
            'message' => 'Version créée avec succès',
            'data' => $appVersion
        ], 201);
    }

    /**
     * Afficher une version spécifique
     */
    public function show(AppVersion $appVersion): JsonResponse
    {
        return response()->json($appVersion);
    }

    /**
     * Mettre à jour une version
     */
    public function update(Request $request, AppVersion $appVersion): JsonResponse
    {
        $validated = $request->validate([
            'platform' => 'sometimes|in:ios,android',
            'version' => 'sometimes|string',
            'build_number' => 'sometimes|integer',
            'is_mandatory' => 'boolean',
            'min_supported_version' => 'nullable|string',
            'release_notes' => 'nullable|string',
            'download_url' => 'nullable|url',
            'is_active' => 'boolean',
            'released_at' => 'nullable|date',
        ]);

        $appVersion->update($validated);

        return response()->json([
            'message' => 'Version mise à jour avec succès',
            'data' => $appVersion
        ]);
    }

    /**
     * Supprimer une version
     */
    public function destroy(AppVersion $appVersion): JsonResponse
    {
        $appVersion->delete();

        return response()->json([
            'message' => 'Version supprimée avec succès'
        ]);
    }

    /**
     * Vérifier la version de l'application
     */
    public function check(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'platform' => 'required|in:ios,android',
            'current_version' => 'required|string',
        ]);

        $latestVersion = AppVersion::getLatestVersion($validated['platform']);

        if (!$latestVersion) {
            return response()->json([
                'message' => 'Aucune version disponible pour cette plateforme'
            ], 404);
        }

        $updateRequired = $latestVersion->isUpdateRequired($validated['current_version']);
        $isSupported = $latestVersion->isSupported($validated['current_version']);

        return response()->json([
            'latest_version' => $latestVersion->version,
            'build_number' => $latestVersion->build_number,
            'update_required' => $updateRequired,
            'is_supported' => $isSupported,
            'is_mandatory' => $latestVersion->is_mandatory,
            'release_notes' => $latestVersion->release_notes,
            'download_url' => $latestVersion->download_url,
        ]);
    }

    /**
     * Obtenir la dernière version pour une plateforme
     */
    public function latest($platform): JsonResponse
    {
        $version = AppVersion::getLatestVersion($platform);

        if (!$version) {
            return response()->json([
                'message' => 'Aucune version disponible'
            ], 404);
        }

        return response()->json($version);
    }

    /**
     * Activer/désactiver une version
     */
    public function toggleActive(AppVersion $appVersion): JsonResponse
    {
        $appVersion->update(['is_active' => !$appVersion->is_active]);

        return response()->json([
            'message' => 'Statut de la version mis à jour',
            'data' => $appVersion
        ]);
    }
}
