<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RobotsTag
{
    /**
     * Default: noindex for the whole application.
     * Exception: allow indexing only for the welcome page (/).
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        // Only touch normal HTTP responses (skip streamed/binary, etc.).
        if (!($response instanceof Response)) {
            return $response;
        }

        // Root path is an empty string in Laravel ("").
        $isWelcome = $request->path() === '';

        $value = $isWelcome ? 'index, follow' : 'noindex, nofollow';

        $response->headers->set('X-Robots-Tag', $value);

        return $response;
    }
}

