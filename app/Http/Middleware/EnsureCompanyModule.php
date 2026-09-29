<?php

namespace App\Http\Middleware;

use App\Services\ContextService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCompanyModule
{
    public function __construct(private ContextService $contextService) {}

    /**
     * Reject the request when the active company does not have the module.
     * Super admins are not exempt: they see the product the selected company has.
     */
    /**
     * $modules is one module key, or several separated by commas.
     * The company needs at least one of them.
     */
    public function handle(Request $request, Closure $next, string $modules): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $company = $this->contextService->effectiveCompany($user);
        $required = array_values(array_filter(explode(',', $modules)));

        if ($company && ! $company->hasAnyModule($required)) {
            abort(403, 'Dieses Modul ist für diese Firma nicht freigeschaltet.');
        }

        return $next($request);
    }
}
