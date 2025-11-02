<?php

namespace App\Http\Controllers;

use App\Services\ContextService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;

abstract class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    protected $contextService;

    public function __construct(ContextService $contextService)
    {
        $this->contextService = $contextService;
    }

    /**
     * Get the current user context
     */
    protected function getUserContext()
    {
        return $this->contextService->getUserContext();
    }

    /**
     * Get the current company context
     */
    protected function getCompanyContext()
    {
        $userContext = $this->getUserContext();
        return $userContext['company'] ?? null;
    }

    /**
     * Get effective company ID for filtering (selected company for super admins, user's company otherwise)
     */
    protected function getEffectiveCompanyId()
    {
        $user = Auth::user();
        if (!$user) {
            return null;
        }

        // If user has manage_companies permission and has selected a company in session, use that
        if (method_exists($user, 'hasPermissionTo') && $user->hasPermissionTo('manage_companies')) {
            $selectedCompanyId = Session::get('selected_company_id');
            if ($selectedCompanyId) {
                return $selectedCompanyId;
            }
        }

        return $user->company_id;
    }

    /**
     * Get dashboard statistics
     */
    protected function getDashboardStats()
    {
        return $this->contextService->getDashboardStats();
    }

    /**
     * Format currency amount
     */
    protected function formatCurrency(float $amount, string $currency = 'EUR'): string
    {
        return $this->contextService->formatCurrency($amount, $currency);
    }

    /**
     * Format date
     */
    protected function formatDate(string $date, string $format = 'd.m.Y'): string
    {
        return $this->contextService->formatDate($date, $format);
    }

    /**
     * Clear user cache
     */
    protected function clearUserCache(): void
    {
        $this->contextService->clearUserCache();
    }

    /**
     * Render Inertia response with additional context
     */
    protected function inertia(string $component, array $props = [])
    {
        return Inertia::render($component, array_merge([
            'user' => $this->getUserContext(),
            'stats' => $this->getDashboardStats(),
        ], $props));
    }

    /**
     * Redirect with success message
     */
    protected function redirectWithSuccess(string $route, string $message, array $parameters = [])
    {
        return redirect()->route($route, $parameters)->with('success', $message);
    }

    /**
     * Redirect with error message
     */
    protected function redirectWithError(string $route, string $message, array $parameters = [])
    {
        return redirect()->route($route, $parameters)->with('error', $message);
    }
}
