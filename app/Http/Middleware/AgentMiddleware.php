<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AgentMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        if (!$user || $user->role !== 'agent') {
            return response()->json([
                'success' => false,
                'message' => 'Accès réservé aux agents.'
            ], 403);
        }

        return $next($request);
    }
}
