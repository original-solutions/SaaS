<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class CorrelationId
{
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = $request->header('X-Request-ID', (string) Str::uuid());

        // Share the ID so it's available via the request
        $request->headers->set('X-Request-ID', $requestId);

        // Add to log context
        Log::shareContext(['request_id' => $requestId]);

        $response = $next($request);

        // Add to response headers
        $response->headers->set('X-Request-ID', $requestId);

        return $response;
    }
}
