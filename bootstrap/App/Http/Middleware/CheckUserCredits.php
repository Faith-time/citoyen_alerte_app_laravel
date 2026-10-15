<?php
// app/Http/Middleware/CheckUserCredits.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckUserCredits
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->role === 'citizen' && !$user->canReport()) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient credits to create a report',
                'credits' => $user->credits,
                'max_credits' => $user->max_credits
            ], 403);
        }

        return $next($request);
    }
}
