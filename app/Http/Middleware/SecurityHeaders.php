<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Generate a per-request nonce for CSP so inline scripts can be whitelisted
        // without resorting to 'unsafe-inline'.
        $nonce = base64_encode(random_bytes(16));
        $request->attributes->set('csp_nonce', $nonce);

        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        if ($request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // CSP is skipped in local/development to keep Vite HMR working.
        if (!app()->environment('local', 'development')) {
            // 'unsafe-eval' is NOT included — Vite production bundles do not need it.
            // Inline scripts in app.blade.php must carry the nonce attribute.
            $csp = "default-src 'self'; " .
                   "script-src 'self' 'nonce-{$nonce}'; " .
                   "style-src 'self' 'unsafe-inline'; " .
                   "font-src 'self' data:; " .
                   "img-src 'self' data: blob: https:; " .
                   "connect-src 'self' https:; " .
                   "frame-src 'self' blob:; " .
                   "frame-ancestors 'self'; " .
                   "base-uri 'self'; " .
                   "form-action 'self';";

            $response->headers->set('Content-Security-Policy', $csp);
        }

        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');

        return $response;
    }
}
