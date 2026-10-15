<?php
// app/Http/Controllers/API/UserBadgeController.php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\UserBadge;
use App\Models\Badge;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserBadgeController extends Controller
{
    public function index(Request $request)
    {
        $query = UserBadge::with(['user', 'badge']);

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('badge_id')) {
            $query->where('badge_id', $request->badge_id);
        }

        $query->orderBy('earned_at', 'desc');

        $userBadges = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $userBadges
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'badge_id' => 'required|exists:badges,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if user already has this badge
        $exists = UserBadge::where('user_id', $request->user_id)
            ->where('badge_id', $request->badge_id)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'User already has this badge'
            ], 422);
        }

        $userBadge = UserBadge::create([
            'user_id' => $request->user_id,
            'badge_id' => $request->badge_id,
            'earned_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Badge awarded successfully',
            'data' => $userBadge->load(['user', 'badge'])
        ], 201);
    }

    public function show(string $id)
    {
        $userBadge = UserBadge::with(['user', 'badge'])->find($id);

        if (!$userBadge) {
            return response()->json([
                'success' => false,
                'message' => 'User badge not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $userBadge
        ]);
    }

    public function update(Request $request, string $id)
    {
        return response()->json([
            'success' => false,
            'message' => 'User badges cannot be updated'
        ], 405);
    }

    public function destroy(string $id)
    {
        $userBadge = UserBadge::find($id);

        if (!$userBadge) {
            return response()->json([
                'success' => false,
                'message' => 'User badge not found'
            ], 404);
        }

        $userBadge->delete();

        return response()->json([
            'success' => true,
            'message' => 'Badge removed successfully'
        ]);
    }

    /**
     * Check and award eligible badges to user
     */
    public function checkEligibility(Request $request, $userId)
    {
        $user = User::find($userId);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        $badges = Badge::active()->get();
        $awardedBadges = [];

        foreach ($badges as $badge) {
            if ($badge->canBeEarnedBy($user)) {
                UserBadge::create([
                    'user_id' => $user->id,
                    'badge_id' => $badge->id,
                    'earned_at' => now(),
                ]);

                $awardedBadges[] = $badge;
            }
        }

        return response()->json([
            'success' => true,
            'message' => count($awardedBadges) . ' badge(s) awarded',
            'data' => $awardedBadges
        ]);
    }
}
