<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogActivity
{
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        // Log optionnel des activités
        if (auth()->check()) {
            Log::info('User activity', [
                'user_id' => auth()->id(),
                'method'  => $request->method(),
                'url'     => $request->url(),
                'status'  => $response->getStatusCode(),
                'ip'      => $request->ip(),
            ]);
        }
    }
}
