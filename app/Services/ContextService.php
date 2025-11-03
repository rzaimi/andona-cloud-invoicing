<?php

namespace App\Services;

use App\Modules\Company\Models\Company;
use App\Modules\Customer\Models\Customer;
use App\Modules\Invoice\Models\Invoice;
use App\Modules\Offer\Models\Offer;
use App\Modules\Product\Models\Product;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;

class ContextService
{
    /**
     * Get the current user context with company and settings
     */
    public function getUserContext(?User $user = null): array
    {
        $user = $user ?? Auth::user();

        if (!$user) {
            return $this->getGuestContext();
        }

        // Cache user context for 5 minutes to improve performance
        return Cache::remember("user_context_{$user->id}", 300, function () use ($user) {
            $user->load(['company']);

            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role ?? 'user',
                'status' => $user->status ?? 'active',
                'company' => $this->getCompanyContext($user->company),
                'permissions' => $this->getUserPermissions($user),
                'preferences' => $this->getUserPreferences($user),
            ];
        });
    }

    /**
     * Get company context with settings
     */
    public function getCompanyContext(?Company $company = null): ?array
    {
        if (!$company) {
            return null;
        }

        return [
            'id' => $company->id,
            'name' => $company->name,
            'email' => $company->email,
            'phone' => $company->phone,
            'address' => $company->address,
            'postal_code' => $company->postal_code,
            'city' => $company->city,
            'country' => $company->country ?? 'Deutschland',
            'tax_number' => $company->tax_number,
            'vat_number' => $company->vat_number,
            'commercial_register' => $company->commercial_register,
            'managing_director' => $company->managing_director,
            'bank_name' => $company->bank_name,
            'bank_iban' => $company->bank_iban,
            'bank_bic' => $company->bank_bic,
            'website' => $company->website,
            'logo' => $company->logo,
            'status' => $company->status ?? 'active',
            'settings' => $this->getCompanySettings($company),
        ];
    }

    /**
     * Get dashboard statistics for the current user's company
     */
    public function getDashboardStats(?User $user = null): array
    {
        $user = $user ?? Auth::user();

        if (!$user || !$user->company_id) {
            return $this->getEmptyStats();
        }

        // For super admins, use selected company from session if available
        $companyId = $this->getEffectiveCompanyId($user);

        return Cache::remember("dashboard_stats_{$companyId}", 300, function () use ($companyId) {

            return [
                'customers' => [
                    'total' => Customer::where('company_id', $companyId)->count(),
                    'active' => Customer::where('company_id', $companyId)->where('status', 'active')->count(),
                    'new_this_month' => Customer::where('company_id', $companyId)
                        ->whereMonth('created_at', now()->month)
                        ->whereYear('created_at', now()->year)
                        ->count(),
                ],
                'invoices' => [
                    'total' => Invoice::where('company_id', $companyId)->count(),
                    'draft' => Invoice::where('company_id', $companyId)->where('status', 'draft')->count(),
                    'sent' => Invoice::where('company_id', $companyId)->where('status', 'sent')->count(),
                    'paid' => Invoice::where('company_id', $companyId)->where('status', 'paid')->count(),
                    'overdue' => Invoice::where('company_id', $companyId)->where('status', 'overdue')->count(),
                    'total_amount' => Invoice::where('company_id', $companyId)->sum('total') ?? 0,
                    'paid_amount' => Invoice::where('company_id', $companyId)->where('status', 'paid')->sum('total') ?? 0,
                    'outstanding_amount' => Invoice::where('company_id', $companyId)
                            ->whereIn('status', ['sent', 'overdue'])
                            ->sum('total') ?? 0,
                ],
                'offers' => [
                    'total' => Offer::where('company_id', $companyId)->count(),
                    'draft' => Offer::where('company_id', $companyId)->where('status', 'draft')->count(),
                    'sent' => Offer::where('company_id', $companyId)->where('status', 'sent')->count(),
                    'accepted' => Offer::where('company_id', $companyId)->where('status', 'accepted')->count(),
                    'rejected' => Offer::where('company_id', $companyId)->where('status', 'rejected')->count(),
                    'expired' => Offer::where('company_id', $companyId)->where('status', 'expired')->count(),
                    'total_amount' => Offer::where('company_id', $companyId)->sum('total') ?? 0,
                ],
                'products' => [
                    'total' => Product::where('company_id', $companyId)->count(),
                    'active' => Product::where('company_id', $companyId)->where('status', 'active')->count(),
                    'low_stock' => Product::where('company_id', $companyId)
                        ->where('track_stock', true)
                        ->whereColumn('stock_quantity', '<=', 'min_stock_level')
                        ->count(),
                    'out_of_stock' => Product::where('company_id', $companyId)
                        ->where('track_stock', true)
                        ->where('stock_quantity', 0)
                        ->count(),
                ],
                'revenue' => [
                    'this_month' => Invoice::where('company_id', $companyId)
                            ->where('status', 'paid')
                            ->whereMonth('created_at', now()->month)
                            ->whereYear('created_at', now()->year)
                            ->sum('total') ?? 0,
                    'last_month' => Invoice::where('company_id', $companyId)
                            ->where('status', 'paid')
                            ->whereMonth('created_at', now()->subMonth()->month)
                            ->whereYear('created_at', now()->subMonth()->year)
                            ->sum('total') ?? 0,
                    'this_year' => Invoice::where('company_id', $companyId)
                            ->where('status', 'paid')
                            ->whereYear('created_at', now()->year)
                            ->sum('total') ?? 0,
                    'last_year' => Invoice::where('company_id', $companyId)
                            ->where('status', 'paid')
                            ->whereYear('created_at', now()->subYear()->year)
                            ->sum('total') ?? 0,
                ],
            ];
        });
    }

    /**
     * Get user permissions based on role
     */
    private function getUserPermissions(User $user): array
    {
        $basePermissions = [
            'view_dashboard' => true,
            'manage_customers' => true,
            'manage_products' => true,
            'manage_invoices' => true,
            'manage_offers' => true,
            'view_reports' => true,
        ];

        $adminPermissions = [
            'manage_users' => true,
            'manage_companies' => true,
            'manage_settings' => true,
            'view_all_data' => true,
            'export_data' => true,
            'manage_layouts' => true,
        ];

        return $user->role === 'admin'
            ? array_merge($basePermissions, $adminPermissions)
            : $basePermissions;
    }

    /**
     * Get user preferences
     */
    private function getUserPreferences(User $user): array
    {
        return [
            'language' => 'de',
            'timezone' => 'Europe/Berlin',
            'currency' => 'EUR',
            'date_format' => 'd.m.Y',
            'time_format' => 'H:i',
            'items_per_page' => 15,
            'theme' => 'light',
        ];
    }

    /**
     * Get company settings as key-value array
     */
    protected function getCompanySettings(Company $company): array
    {
        $defaultSettings = [
            'invoice_prefix' => 'RE',
            'offer_prefix' => 'AN',
            'customer_prefix' => 'KU',
            'product_prefix' => 'PR',
            'default_tax_rate' => 0.19,
            'default_currency' => 'EUR',
            'default_payment_terms' => '14 Tage netto',
            'default_due_days' => 14,
            'default_offer_validity_days' => 30,
            'enable_stock_tracking' => true,
            'low_stock_threshold' => 10,
            'auto_send_reminders' => false,
            'reminder_days_before_due' => 3,
            'reminder_days_after_due' => 7,
        ];

        // Try to get company settings, but handle null case
        try {
            if ($company->relationLoaded('settings')) {
                $companySettings = $company->settings->pluck('value', 'key')->toArray();
            } else {
                $companySettings = $company->settings()->pluck('value', 'key')->toArray();
            }
        } catch (\Exception $e) {
            $companySettings = [];
        }

        return array_merge($defaultSettings, $companySettings);
    }

    /**
     * Get guest context for non-authenticated users
     */
    protected function getGuestContext(): array
    {
        return [
            'id' => null,
            'name' => 'Gast',
            'email' => null,
            'role' => 'guest',
            'status' => 'inactive',
            'company' => null,
            'permissions' => [],
            'preferences' => $this->getDefaultPreferences(),
        ];
    }

    /**
     * Get empty statistics
     */
    private function getEmptyStats(): array
    {
        return [
            'customers' => ['total' => 0, 'active' => 0, 'new_this_month' => 0],
            'invoices' => [
                'total' => 0, 'draft' => 0, 'sent' => 0, 'paid' => 0, 'overdue' => 0,
                'total_amount' => 0, 'paid_amount' => 0, 'outstanding_amount' => 0
            ],
            'offers' => [
                'total' => 0, 'draft' => 0, 'sent' => 0, 'accepted' => 0,
                'rejected' => 0, 'expired' => 0, 'total_amount' => 0
            ],
            'products' => ['total' => 0, 'active' => 0, 'low_stock' => 0, 'out_of_stock' => 0],
            'revenue' => ['this_month' => 0, 'last_month' => 0, 'this_year' => 0, 'last_year' => 0],
        ];
    }

    /**
     * Get default preferences
     */
    private function getDefaultPreferences(): array
    {
        return [
            'language' => 'de',
            'timezone' => 'Europe/Berlin',
            'currency' => 'EUR',
            'date_format' => 'd.m.Y',
            'time_format' => 'H:i',
            'items_per_page' => 15,
            'theme' => 'light',
        ];
    }

    /**
     * Get effective company ID for filtering (selected company for super admins, user's company otherwise)
     */
    protected function getEffectiveCompanyId(User $user): ?string
    {
        // If user has manage_companies permission and has selected a company in session, use that
        if (method_exists($user, 'hasPermissionTo') && $user->hasPermissionTo('manage_companies')) {
            $selectedCompanyId = Session::get('selected_company_id');
            
            // Validate that the selected company still exists and is active
            if ($selectedCompanyId) {
                $selectedCompany = Company::find($selectedCompanyId);
                if ($selectedCompany && $selectedCompany->status === 'active') {
                    return $selectedCompanyId;
                } else {
                    // Company doesn't exist or is inactive, clear session and fallback
                    Session::forget('selected_company_id');
                }
            }
            
            // If no valid session company, try to get first available company
            $firstCompany = Company::where('status', 'active')
                ->orderBy('name')
                ->first();
            if ($firstCompany) {
                // Auto-select first company for consistency
                Session::put('selected_company_id', $firstCompany->id);
                return $firstCompany->id;
            }
        }
        
        // Fallback to user's own company
        return $user->company_id;
    }

    /**
     * Clear user context cache
     */
    public function clearUserCache(?User $user = null): void
    {
        $user = $user ?? Auth::user();

        if ($user) {
            Cache::forget("user_context_{$user->id}");
            $companyId = $this->getEffectiveCompanyId($user);
            if ($companyId) {
                Cache::forget("dashboard_stats_{$companyId}");
            }
        }
    }

    /**
     * Get formatted currency amount
     */
    public function formatCurrency(float $amount, string $currency = 'EUR'): string
    {
        return number_format($amount, 2, ',', '.') . ' ' . $currency;
    }

    /**
     * Get formatted date
     */
    public function formatDate(string $date, string $format = 'd.m.Y'): string
    {
        return \Carbon\Carbon::parse($date)->format($format);
    }

    /**
     * Get formatted date and time
     */
    public function formatDateTime(string $dateTime, string $format = 'd.m.Y H:i'): string
    {
        return \Carbon\Carbon::parse($dateTime)->format($format);
    }
}
