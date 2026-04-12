<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Employees (role = 'employee') must only access the /portal/* routes.
 * Any other authenticated request is redirected to the portal documents page.
 */
class RedirectEmployeeToPortal
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->role === 'employee') {
            // Allow portal routes and the logout route through
            if (
                $request->routeIs('portal.*') ||
                $request->routeIs('logout') ||
                $request->routeIs('password.*') ||
                $request->routeIs('verification.*')
            ) {
                return $next($request);
            }

            return redirect()->route('portal.documents');
        }

        return $next($request);
    }
}
