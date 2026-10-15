<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckAuthority
{
    public function handle(Request $request, Closure $next)
    {
        if (auth()->user()?->role !== 'authority') {
            return response()->json(['message' => 'Accès réservé aux autorités'], 403);
        }
        return $next($request);
    }
}
