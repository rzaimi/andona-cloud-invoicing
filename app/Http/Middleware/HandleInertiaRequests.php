<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

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
                    
                    $userData = array_merge($user->toArray(), [
                        'roles' => $roles,
                        'permissions' => $permissions,
                    ]);
                    
                    // For super admins with manage_companies permission, use selected company from session if available
                    if (method_exists($user, 'hasPermissionTo') && $user->hasPermissionTo('manage_companies')) {
                        $selectedCompanyId = Session::get('selected_company_id');
                        if ($selectedCompanyId) {
                            $selectedCompany = \App\Modules\Company\Models\Company::find($selectedCompanyId);
                            if ($selectedCompany) {
                                $userData['company'] = [
                                    'id' => $selectedCompany->id,
                                    'name' => $selectedCompany->name,
                                ];
                            }
                        }
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
            'ziggy' => fn (): array => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
