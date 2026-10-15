<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckUserBanned
{
    public function handle(Request $request, Closure $next)
    {
        if (auth()->check() && auth()->user()->is_banned) {
            return response()->json([
                'success' => false,
                'message' => 'Votre compte a été suspendu.',
            ], 403);
        }
        return $next($request);
    }
}
