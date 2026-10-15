<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\AgentZone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthorityAgentController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        /** @var User $authority */
        $authority = $request->user();

        // ✅ Vérification manuelle du rôle
        if ($authority->role !== 'authority') {
            return response()->json(['success' => false, 'message' => 'Non autorisé'], 403);
        }

        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'phone'    => 'nullable|string|unique:users,phone',
            'password' => 'required|string|min:8',
        ]);

        /** @var User $agent */
        $agent = User::create([
            'name'         => $validated['name'],
            'email'        => $validated['email'],
            'phone'        => $validated['phone'] ?? null,
            'password'     => Hash::make($validated['password']),
            'role'         => 'agent',
            'authority_id' => $authority->authority_id,
            'is_active'    => true,
            'is_verified'  => true,
        ]);

        /** @var AgentZone $zone */
        AgentZone::create([
            'agent_id'     => $agent->id,
            'authority_id' => $authority->authority_id,
            'is_primary'   => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Agent créé avec succès',
            'data'    => $agent,
        ], 201);
    }

    public function update(Request $request, $userId): JsonResponse
    {
        /** @var User $authority */
        $authority = $request->user();

        if ($authority->role !== 'authority') {
            return response()->json(['success' => false, 'message' => 'Non autorisé'], 403);
        }

        $user = User::findOrFail($userId);

        if ($user->authority_id !== $authority->authority_id) {
            return response()->json(['success' => false, 'message' => 'Non autorisé'], 403);
        }

        $validated = $request->validate([
            'name'  => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|unique:users,phone,' . $user->id,
        ]);

        $user->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Agent mis à jour',
            'data'    => $user,
        ]);
    }

    public function destroy(Request $request, $userId): JsonResponse
    {
        /** @var User $authority */
        $authority = $request->user();

        if ($authority->role !== 'authority') {
            return response()->json(['success' => false, 'message' => 'Non autorisé'], 403);
        }

        $user = User::findOrFail($userId);

        if ($user->authority_id !== $authority->authority_id) {
            return response()->json(['success' => false, 'message' => 'Non autorisé'], 403);
        }

        AgentZone::where('agent_id', $user->id)->delete();
        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Agent supprimé',
        ]);
    }
}
