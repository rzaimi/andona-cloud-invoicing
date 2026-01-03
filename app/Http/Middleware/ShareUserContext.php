<?php

namespace App\Http\Middleware;

use App\Services\ContextService;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ShareUserContext
{
    protected $contextService;

    public function __construct(ContextService $contextService)
    {
        $this->contextService = $contextService;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Share user context with all Inertia responses
        Inertia::share([
            'auth' => function () {
                return [
                    'user' => $this->contextService->getUserContext(),
                ];
            },
            'flash' => function () use ($request) {
                return [
                    'success' => $request->session()->get('success'),
                    'error' => $request->session()->get('error'),
                    'warning' => $request->session()->get('warning'),
                    'info' => $request->session()->get('info'),
                ];
            },
            // Note: stats are only shared on dashboard page, not globally
            // This reduces payload size on other pages
        ]);

        return $next($request);
    }
}
