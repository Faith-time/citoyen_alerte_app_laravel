<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Badge;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class BadgeController extends Controller
{
    /**
     * Afficher la liste des badges
     */
    public function index(Request $request): JsonResponse
    {
        $query = Badge::query();

        // Filtre par catégorie
        if ($request->has('category')) {
            $query->byCategory($request->category);
        }

        // Filtre par rareté
        if ($request->has('rarity')) {
            $query->byRarity($request->rarity);
        }

        // Badges actifs uniquement
        if ($request->boolean('active_only')) {
            $query->active();
        }

        $badges = $query->paginate($request->get('per_page', 15));

        return response()->json($badges);
    }

    /**
     * Créer un nouveau badge
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|unique:badges,slug',
            'description' => 'nullable|string',
            'icon' => 'nullable|string',
            'category' => 'required|string',
            'points_required' => 'nullable|integer|min:0',
            'reports_required' => 'nullable|integer|min:0',
            'conditions' => 'nullable|array',
            'rarity' => 'required|in:common,rare,epic,legendary',
            'is_active' => 'boolean',
        ]);

        $badge = Badge::create($validated);

        return response()->json([
            'message' => 'Badge créé avec succès',
            'data' => $badge
        ], 201);
    }

    /**
     * Afficher un badge spécifique
     */
    public function show(Badge $badge): JsonResponse
    {
        $badge->loadCount('users');

        return response()->json($badge);
    }

    /**
     * Mettre à jour un badge
     */
    public function update(Request $request, Badge $badge): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'slug' => 'sometimes|string|unique:badges,slug,' . $badge->id,
            'description' => 'nullable|string',
            'icon' => 'nullable|string',
            'category' => 'sometimes|string',
            'points_required' => 'nullable|integer|min:0',
            'reports_required' => 'nullable|integer|min:0',
            'conditions' => 'nullable|array',
            'rarity' => 'sometimes|in:common,rare,epic,legendary',
            'is_active' => 'boolean',
        ]);

        $badge->update($validated);

        return response()->json([
            'message' => 'Badge mis à jour avec succès',
            'data' => $badge
        ]);
    }

    /**
     * Supprimer un badge
     */
    public function destroy(Badge $badge): JsonResponse
    {
        $badge->delete();

        return response()->json([
            'message' => 'Badge supprimé avec succès'
        ]);
    }

    /**
     * Attribuer un badge à un utilisateur
     */
    public function assign(Request $request, Badge $badge): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $user = User::findOrFail($validated['user_id']);

        // Vérifier si l'utilisateur a déjà ce badge
        if ($user->badges()->where('badge_id', $badge->id)->exists()) {
            return response()->json([
                'message' => 'L\'utilisateur possède déjà ce badge'
            ], 422);
        }

        $user->badges()->attach($badge->id, ['earned_at' => now()]);

        return response()->json([
            'message' => 'Badge attribué avec succès',
            'data' => [
                'user' => $user,
                'badge' => $badge,
                'earned_at' => now()
            ]
        ]);
    }

    /**
     * Retirer un badge d'un utilisateur
     */
    public function revoke(Request $request, Badge $badge): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $user = User::findOrFail($validated['user_id']);
        $user->badges()->detach($badge->id);

        return response()->json([
            'message' => 'Badge retiré avec succès'
        ]);
    }

    /**
     * Vérifier si un utilisateur peut gagner un badge
     */
    public function checkEligibility(Badge $badge, User $user): JsonResponse
    {
        $canEarn = $badge->canBeEarnedBy($user);

        $data = [
            'can_earn' => $canEarn,
            'badge' => $badge,
            'user_progress' => [
                'current_points' => $user->reputation_score ?? 0,
                'required_points' => $badge->points_required,
                'current_reports' => $user->reports()->count(),
                'required_reports' => $badge->reports_required,
            ]
        ];

        return response()->json($data);
    }

    /**
     * Obtenir les badges d'un utilisateur
     */
    public function userBadges(User $user): JsonResponse
    {
        $badges = $user->badges()
            ->withPivot('earned_at')
            ->orderByDesc('pivot_earned_at')
            ->get();

        return response()->json($badges);
    }

    /**
     * Obtenir les utilisateurs qui ont un badge
     */
    public function badgeHolders(Badge $badge, Request $request): JsonResponse
    {
        $users = $badge->users()
            ->withPivot('earned_at')
            ->orderByDesc('pivot_earned_at')
            ->paginate($request->get('per_page', 15));

        return response()->json($users);
    }

    /**
     * Activer/désactiver un badge
     */
    public function toggleActive(Badge $badge): JsonResponse
    {
        $badge->update(['is_active' => !$badge->is_active]);

        return response()->json([
            'message' => 'Statut du badge mis à jour',
            'data' => $badge
        ]);
    }
}
