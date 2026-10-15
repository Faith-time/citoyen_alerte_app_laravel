<?php
// app/Http/Middleware/CheckUserVerified.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckUserVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && !$user->is_verified) {
            return response()->json([
                'success' => false,
                'message' => 'Please verify your account to continue',
                'email_verified' => !is_null($user->email_verified_at),
                'phone_verified' => !is_null($user->phone_verified_at)
            ], 403);
        }

        return $next($request);
    }
}
