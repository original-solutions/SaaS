<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $nonce = base64_encode(random_bytes(16));
        $request->attributes->set('csp_nonce', $nonce);

        /** @var Response $response */
        $response = $next($request);

        $response->headers->set(
            'Content-Security-Policy',
            $this->buildCsp($nonce),
        );
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        return $response;
    }

    /**
     * Build the Content-Security-Policy header value.
     */
    protected function buildCsp(string $nonce): string
    {
        $isLocal = app()->environment('local');
        $viteDevServer = $isLocal
            ? ' '.config('app.url').':'.env('VITE_PORT', 5173)
            : '';

        $directives = [
            "default-src 'self'",
            "script-src 'self' 'nonce-{$nonce}'{$viteDevServer}",
            $isLocal
                ? "style-src 'self' 'unsafe-inline'{$viteDevServer}"
                : "style-src 'self' 'nonce-{$nonce}'",
            "img-src 'self' data: https:",
            "font-src 'self' data:",
            "connect-src 'self' wss:{$viteDevServer}",
            "frame-ancestors 'none'",
            "base-uri 'self'",
            "form-action 'self'",
        ];

        return implode('; ', $directives);
    }
}
