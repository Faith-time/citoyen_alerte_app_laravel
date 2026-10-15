<?php
// app/Http/Middleware/CheckUserBanned.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckUserBanned
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->is_banned) {
            return response()->json([
                'success' => false,
                'message' => 'Your account has been permanently banned',
                'reason' => $user->ban_reason,
                'banned_at' => $user->banned_at
            ], 403);
        }

        return $next($request);
    }
}
