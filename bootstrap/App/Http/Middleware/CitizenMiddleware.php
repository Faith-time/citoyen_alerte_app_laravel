<?php
// app/Http/Middleware/CitizenMiddleware.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CitizenMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user() || $request->user()->role !== 'citizen') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Citizen access required.'
            ], 403);
        }

        return $next($request);
    }
}
