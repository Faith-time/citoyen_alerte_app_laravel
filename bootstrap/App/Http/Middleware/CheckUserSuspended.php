<?php
// app/Http/Middleware/CheckUserSuspended.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckUserSuspended
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->is_suspended) {
            if ($user->suspended_until && $user->suspended_until > now()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your account is suspended',
                    'suspended_until' => $user->suspended_until,
                    'reason' => $user->suspension_reason
                ], 403);
            } else {
                // Auto unsuspend if period expired
                $user->update([
                    'is_suspended' => false,
                    'suspended_until' => null,
                    'suspension_reason' => null
                ]);
            }
        }

        return $next($request);
    }
}
