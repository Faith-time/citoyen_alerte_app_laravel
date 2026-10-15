<?php
namespace App\Http\Middleware;
use Closure;

class CheckAuthority
{
    public function handle($request, Closure $next)
    {
        $user = $request->user();
        if (!$user || $user->role !== 'authority') {
            return response()->json([
                'success' => false,
                'message' => 'Accès réservé aux autorités.'
            ], 403);
        }
        return $next($request);
    }
}
