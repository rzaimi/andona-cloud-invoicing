<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CheckSessionTimeout
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $session = $request->session();
            $lastActivity = $session->get('last_activity');
            $timeout = config('session.lifetime', 120) * 60; // Convert minutes to seconds
            
            // Initialize last_activity if not set (for existing sessions)
            if (!$lastActivity) {
                $session->put('last_activity', time());
                return $next($request);
            }
            
            // Check if session has expired
            if ((time() - $lastActivity) > $timeout) {
                Auth::logout();
                $session->invalidate();
                $session->regenerateToken();
                
                return redirect()->route('login')
                    ->with('status', 'Ihre Sitzung ist abgelaufen. Bitte melden Sie sich erneut an.');
            }
            
            // Update last activity timestamp
            $session->put('last_activity', time());
            
            // Optional: Check if IP or user agent changed (security check)
            $loginIp = $session->get('login_ip');
            $loginUserAgent = $session->get('login_user_agent');
            
            if ($loginIp && $loginIp !== $request->ip()) {
                // IP changed - log this but don't block (could be legitimate)
                Log::warning('Session IP changed', [
                    'user_id' => Auth::id(),
                    'old_ip' => $loginIp,
                    'new_ip' => $request->ip(),
                ]);
            }
        }
        
        return $next($request);
    }
}
