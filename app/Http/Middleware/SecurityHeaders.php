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
        $response = $next($request);

        // Security Headers
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        
        // HSTS (HTTP Strict Transport Security) - only for HTTPS
        if ($request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // Content Security Policy
        // In development, use report-only mode or disable to allow Vite dev server
        $isDevelopment = app()->environment('local', 'development');
        
        if (!$isDevelopment) {
            // Production: strict CSP - all resources are local, no external dependencies
            $csp = "default-src 'self'; " .
                   "script-src 'self' 'unsafe-inline' 'unsafe-eval'; " .
                   "style-src 'self' 'unsafe-inline'; " .
                   "font-src 'self' data:; " .
                   "img-src 'self' data: https:; " .
                   "connect-src 'self' https:; " .
                   "frame-ancestors 'self'; " .
                   "base-uri 'self'; " .
                   "form-action 'self';";
            
            $response->headers->set('Content-Security-Policy', $csp);
        }
        // In development, we skip CSP to avoid conflicts with Vite dev server
        // Security headers other than CSP are still applied

        // Permissions Policy (formerly Feature Policy)
        $response->headers->set('Permissions-Policy', 
            'geolocation=(), microphone=(), camera=()'
        );

        return $response;
    }
}
