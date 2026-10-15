<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SettingController extends Controller
{
    /**
     * Afficher la liste des paramètres
     */
    public function index(Request $request): JsonResponse
    {
        $query = Setting::query();

        // Filtre par groupe
        if ($request->has('group')) {
            $query->byGroup($request->group);
        }

        // Paramètres publics uniquement
        if ($request->boolean('public_only')) {
            $query->public();
        }

        $settings = $query->paginate($request->get('per_page', 20));

        return response()->json($settings);
    }

    /**
     * Créer un nouveau paramètre
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'key' => 'required|string|unique:settings,key',
            'value' => 'required',
            'type' => 'required|in:string,integer,boolean,json',
            'group_name' => 'nullable|string',
            'description' => 'nullable|string',
            'is_public' => 'boolean',
        ]);

        // Convertir la valeur selon le type
        if ($validated['type'] === 'json' && is_array($validated['value'])) {
            $validated['value'] = json_encode($validated['value']);
        }

        $setting = Setting::create($validated);

        return response()->json([
            'message' => 'Paramètre créé avec succès',
            'data' => $setting
        ], 201);
    }

    /**
     * Afficher un paramètre spécifique
     */
    public function show(Setting $setting): JsonResponse
    {
        return response()->json([
            'setting' => $setting,
            'casted_value' => $setting->getCastedValue()
        ]);
    }

    /**
     * Mettre à jour un paramètre
     */
    public function update(Request $request, Setting $setting): JsonResponse
    {
        $validated = $request->validate([
            'value' => 'sometimes',
            'type' => 'sometimes|in:string,integer,boolean,json',
            'group_name' => 'nullable|string',
            'description' => 'nullable|string',
            'is_public' => 'boolean',
        ]);

        // Convertir la valeur selon le type
        if (isset($validated['type']) && $validated['type'] === 'json' && isset($validated['value']) && is_array($validated['value'])) {
            $validated['value'] = json_encode($validated['value']);
        }

        $setting->update($validated);

        return response()->json([
            'message' => 'Paramètre mis à jour avec succès',
            'data' => $setting
        ]);
    }

    /**
     * Supprimer un paramètre
     */
    public function destroy(Setting $setting): JsonResponse
    {
        $setting->delete();

        return response()->json([
            'message' => 'Paramètre supprimé avec succès'
        ]);
    }

    /**
     * Obtenir la valeur d'un paramètre par sa clé
     */
    public function getValue($key): JsonResponse
    {
        $value = Setting::get($key);

        if ($value === null) {
            return response()->json([
                'message' => 'Paramètre non trouvé'
            ], 404);
        }

        return response()->json([
            'key' => $key,
            'value' => $value
        ]);
    }

    /**
     * Définir la valeur d'un paramètre
     */
    public function setValue(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'key' => 'required|string',
            'value' => 'required',
            'type' => 'sometimes|in:string,integer,boolean,json',
        ]);

        $type = $validated['type'] ?? 'string';
        $value = $validated['value'];

        // Convertir la valeur selon le type
        if ($type === 'json' && is_array($value)) {
            $value = json_encode($value);
        }

        $setting = Setting::set($validated['key'], $value, $type);

        return response()->json([
            'message' => 'Paramètre défini avec succès',
            'data' => $setting
        ]);
    }

    /**
     * Obtenir tous les paramètres d'un groupe
     */
    public function getByGroup($groupName): JsonResponse
    {
        $settings = Setting::byGroup($groupName)->get();

        $data = $settings->mapWithKeys(function ($setting) {
            return [$setting->key => $setting->getCastedValue()];
        });

        return response()->json($data);
    }

    /**
     * Obtenir tous les paramètres publics
     */
    public function getPublic(): JsonResponse
    {
        $settings = Setting::public()->get();

        $data = $settings->mapWithKeys(function ($setting) {
            return [$setting->key => $setting->getCastedValue()];
        });

        return response()->json($data);
    }

    /**
     * Mettre à jour plusieurs paramètres en une fois
     */
    public function updateBulk(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'settings' => 'required|array',
            'settings.*.key' => 'required|string',
            'settings.*.value' => 'required',
            'settings.*.type' => 'sometimes|in:string,integer,boolean,json',
        ]);

        $updated = [];

        foreach ($validated['settings'] as $settingData) {
            $type = $settingData['type'] ?? 'string';
            $value = $settingData['value'];

            if ($type === 'json' && is_array($value)) {
                $value = json_encode($value);
            }

            $setting = Setting::set($settingData['key'], $value, $type);
            $updated[] = $setting;
        }

        return response()->json([
            'message' => 'Paramètres mis à jour avec succès',
            'count' => count($updated),
            'data' => $updated
        ]);
    }

    /**
     * Obtenir tous les groupes de paramètres
     */
    public function getGroups(): JsonResponse
    {
        $groups = Setting::selectRaw('group_name, COUNT(*) as count')
            ->whereNotNull('group_name')
            ->groupBy('group_name')
            ->get();

        return response()->json($groups);
    }

    /**
     * Réinitialiser un paramètre à sa valeur par défaut
     */
    public function reset($key): JsonResponse
    {
        // Cette méthode pourrait réinitialiser à une valeur par défaut
        // Pour l'instant, on supprime juste le paramètre
        $setting = Setting::where('key', $key)->first();

        if (!$setting) {
            return response()->json([
                'message' => 'Paramètre non trouvé'
            ], 404);
        }

        $setting->delete();

        return response()->json([
            'message' => 'Paramètre réinitialisé avec succès'
        ]);
    }

    /**
     * Exporter tous les paramètres
     */
    public function export(): JsonResponse
    {
        $settings = Setting::all();

        $data = $settings->map(function ($setting) {
            return [
                'key' => $setting->key,
                'value' => $setting->getCastedValue(),
                'type' => $setting->type,
                'group_name' => $setting->group_name,
                'description' => $setting->description,
            ];
        });

        return response()->json($data);
    }

    /**
     * Importer des paramètres
     */
    public function import(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'settings' => 'required|array',
            'settings.*.key' => 'required|string',
            'settings.*.value' => 'required',
            'settings.*.type' => 'required|in:string,integer,boolean,json',
            'settings.*.group_name' => 'nullable|string',
            'settings.*.description' => 'nullable|string',
        ]);

        $imported = 0;

        foreach ($validated['settings'] as $settingData) {
            $value = $settingData['value'];

            if ($settingData['type'] === 'json' && is_array($value)) {
                $value = json_encode($value);
            }

            Setting::updateOrCreate(
                ['key' => $settingData['key']],
                [
                    'value' => $value,
                    'type' => $settingData['type'],
                    'group_name' => $settingData['group_name'] ?? null,
                    'description' => $settingData['description'] ?? null,
                ]
            );

            $imported++;
        }

        return response()->json([
            'message' => 'Paramètres importés avec succès',
            'count' => $imported
        ]);
    }
}
