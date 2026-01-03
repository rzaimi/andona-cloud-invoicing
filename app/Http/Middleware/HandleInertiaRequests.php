<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
                'upload_errors' => $request->session()->get('upload_errors'),
            ],
            'auth' => [
                'user' => function () use ($request) {
                    $user = $request->user();
                    if (!$user) {
                        return null;
                    }
                    
                    $roles = [];
                    $permissions = [];
                    
                    if (method_exists($user, 'getRoleNames')) {
                        $roles = $user->getRoleNames();
                    }
                    
                    if (method_exists($user, 'getAllPermissions')) {
                        // Get all permissions (both direct and through roles)
                        $permissions = $user->getAllPermissions()->pluck('name')->toArray();
                    } elseif (method_exists($user, 'getPermissionNames')) {
                        $permissions = $user->getPermissionNames();
                    }
                    
                    // Only include essential user fields to reduce payload
                    $userData = [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'email_verified_at' => $user->email_verified_at,
                        'created_at' => $user->created_at,
                        'updated_at' => $user->updated_at,
                        'company_id' => $user->company_id,
                        'role' => $user->role ?? 'user',
                        'status' => $user->status ?? 'active',
                        'roles' => $roles,
                        'permissions' => $permissions,
                    ];
                    
                    // Ensure user always has a company selected
                    $company = null;
                    
                    // For super admins with manage_companies permission, use selected company from session if available
                    if (method_exists($user, 'hasPermissionTo') && $user->hasPermissionTo('manage_companies')) {
                        $selectedCompanyId = Session::get('selected_company_id');
                        
                        // Validate that the selected company from session still exists
                        if ($selectedCompanyId) {
                            $selectedCompany = \App\Modules\Company\Models\Company::find($selectedCompanyId);
                            if ($selectedCompany && $selectedCompany->status === 'active') {
                                $company = $selectedCompany;
                            } else {
                                // Selected company doesn't exist or is inactive, clear session
                                Session::forget('selected_company_id');
                            }
                        }
                        
                        // If no valid company from session, try to get default company first
                        if (!$company) {
                            $defaultCompany = \App\Modules\Company\Models\Company::getDefault();
                            if ($defaultCompany) {
                                $company = $defaultCompany;
                                // Auto-select default company in session for consistency
                                Session::put('selected_company_id', $defaultCompany->id);
                            } else {
                                // Fallback to first available company if no default
                                $firstCompany = \App\Modules\Company\Models\Company::where('status', 'active')
                                    ->orderBy('name')
                                    ->first();
                                if ($firstCompany) {
                                    $company = $firstCompany;
                                    Session::put('selected_company_id', $firstCompany->id);
                                }
                            }
                        }
                    }
                    
                    // Fallback to user's own company if no company selected yet
                    if (!$company && $user->company_id) {
                        $userCompany = \App\Modules\Company\Models\Company::find($user->company_id);
                        if ($userCompany && $userCompany->status === 'active') {
                            $company = $userCompany;
                        }
                    }
                    
                    // Set company in userData if we found one - only essential fields
                    if ($company) {
                        // Only include essential company settings, not all settings
                        $settingsService = app(\App\Services\SettingsService::class);
                        $allSettings = $settingsService->getAll($company->id);
                        
                        // Only include frequently used settings to reduce payload
                        $essentialSettings = [
                            'currency' => $allSettings['currency'] ?? 'EUR',
                            'tax_rate' => $allSettings['tax_rate'] ?? 0.19,
                            'invoice_prefix' => $allSettings['invoice_prefix'] ?? 'RE-',
                            'offer_prefix' => $allSettings['offer_prefix'] ?? 'AN-',
                            'date_format' => $allSettings['date_format'] ?? 'd.m.Y',
                            'language' => $allSettings['language'] ?? 'de',
                        ];
                        
                        $userData['company'] = [
                            'id' => $company->id,
                            'name' => $company->name,
                            'logo' => $company->logo,
                            'settings' => $essentialSettings,
                        ];
                    }
                    
                    return $userData;
                },
                'available_companies' => function () use ($request) {
                    if (!$request->user()) {
                        return [];
                    }
                    $user = $request->user();
                    if (method_exists($user, 'hasPermissionTo') && $user->hasPermissionTo('manage_companies')) {
                        return \App\Modules\Company\Models\Company::where('status', 'active')
                            ->orderBy('name')
                            ->get(['id', 'name'])
                            ->map(fn($c) => ['id' => $c->id, 'name' => $c->name])
                            ->toArray();
                    }
                    return [];
                },
            ],
            // Ziggy routes are loaded from generated resources/js/ziggy.js file
            // This removes routes from HTML payload (generated via: php artisan ziggy:generate)
            // No need to include routes in Inertia props anymore
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
