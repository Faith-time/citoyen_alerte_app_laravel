<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    /**
     * Afficher la liste des catégories
     */
    public function index(Request $request): JsonResponse
    {
        $query = Category::query();

        if ($request->boolean('active_only', true)) { // actives par défaut
            $query->active();
        }
        if ($request->boolean('root_only')) {
            $query->rootCategories();
        }
        if ($request->boolean('subcategories_only')) {
            $query->subCategories();
        }
        if ($request->boolean('with_children')) {
            $query->with('children');
        }
        if ($request->boolean('with_parent')) {
            $query->with('parent');
        }

        // Retourner TOUTES les catégories sans pagination
        $categories = $query->orderBy('parent_id')->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data'    => $categories,
        ]);
    }
    /**
     * Créer une nouvelle catégorie
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:categories,id',
            'icon' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $category = Category::create($validated);
        $category->load(['parent', 'children']);

        return response()->json([
            'message' => 'Catégorie créée avec succès',
            'data' => $category
        ], 201);
    }

    /**
     * Afficher une catégorie spécifique
     */
    public function show(Category $category): JsonResponse
    {
        $category->load(['parent', 'children']);
        $category->loadCount('reports');

        return response()->json($category);
    }

    /**
     * Mettre à jour une catégorie
     */
    public function update(Request $request, Category $category): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:categories,id',
            'icon' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        // Empêcher qu'une catégorie soit son propre parent
        if (isset($validated['parent_id']) && $validated['parent_id'] == $category->id) {
            return response()->json([
                'message' => 'Une catégorie ne peut pas être son propre parent'
            ], 422);
        }

        $category->update($validated);
        $category->load(['parent', 'children']);

        return response()->json([
            'message' => 'Catégorie mise à jour avec succès',
            'data' => $category
        ]);
    }

    /**
     * Supprimer une catégorie
     */
    public function destroy(Category $category): JsonResponse
    {
        // Vérifier s'il y a des signalements associés
        if ($category->reports()->exists()) {
            return response()->json([
                'message' => 'Impossible de supprimer une catégorie avec des signalements associés'
            ], 422);
        }

        // Vérifier s'il y a des sous-catégories
        if ($category->hasChildren()) {
            return response()->json([
                'message' => 'Impossible de supprimer une catégorie avec des sous-catégories'
            ], 422);
        }

        $category->delete();

        return response()->json([
            'message' => 'Catégorie supprimée avec succès'
        ]);
    }

    /**
     * Obtenir l'arbre des catégories
     */
    public function tree(): JsonResponse
    {
        $categories = Category::rootCategories()
            ->active()
            ->with(['children' => function ($query) {
                $query->active();
            }])
            ->get();

        return response()->json($categories);
    }

    /**
     * Obtenir les sous-catégories d'une catégorie
     */
    public function children(Category $category): JsonResponse
    {
        $children = $category->children()->active()->get();

        return response()->json($children);
    }

    /**
     * Obtenir les signalements d'une catégorie
     */
    public function reports(Category $category, Request $request): JsonResponse
    {
        $query = $category->reports()->with(['citizen', 'category']);

        // Filtre par statut
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $reports = $query->latest()->paginate($request->get('per_page', 15));

        return response()->json($reports);
    }

    /**
     * Obtenir les statistiques d'une catégorie
     */
    public function statistics(Category $category): JsonResponse
    {
        $stats = [
            'total_reports' => $category->reports()->count(),
            'pending' => $category->reports()->pending()->count(),
            'in_progress' => $category->reports()->inProgress()->count(),
            'resolved' => $category->reports()->resolved()->count(),
            'subcategories_count' => $category->children()->count(),
            'recent_reports' => $category->reports()
                ->latest()
                ->limit(5)
                ->get()
        ];

        return response()->json($stats);
    }

    /**
     * Activer/désactiver une catégorie
     */
    public function toggleActive(Category $category): JsonResponse
    {
        $category->update(['is_active' => !$category->is_active]);

        return response()->json([
            'message' => 'Statut de la catégorie mis à jour',
            'data' => $category
        ]);
    }

    /**
     * Réorganiser les catégories
     */
    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'categories' => 'required|array',
            'categories.*.id' => 'required|exists:categories,id',
            'categories.*.parent_id' => 'nullable|exists:categories,id',
            'categories.*.order' => 'required|integer',
        ]);

        foreach ($validated['categories'] as $categoryData) {
            Category::where('id', $categoryData['id'])->update([
                'parent_id' => $categoryData['parent_id'] ?? null,
            ]);
        }

        return response()->json([
            'message' => 'Catégories réorganisées avec succès'
        ]);
    }
}
