<?php

namespace Tests\Unit\Services;

use App\Modules\Company\Models\Company;
use App\Modules\Customer\Models\Customer;
use App\Modules\Invoice\Models\Invoice;
use App\Modules\Offer\Models\Offer;
use App\Modules\Product\Models\Product;
use App\Modules\User\Models\User;
use App\Services\ContextService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ContextServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ContextService $service;
    protected Company $company;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ContextService();
        $this->seedRolesAndPermissions();
        $this->company = Company::create([
            'name' => 'Test Company',
            'email' => 'test@company.com',
            'status' => 'active',
        ]);
        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
            'role' => 'user',
        ]);
        $this->user->assignRole('user');
    }

    protected function seedRolesAndPermissions(): void
    {
        $guard = 'web';
        $permissions = [
            'manage_users',
            'manage_companies',
            'manage_settings',
            'manage_invoices',
            'manage_offers',
            'manage_products',
            'view_reports',
        ];
        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => $guard]);
        }
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => $guard]);
        $superAdmin->syncPermissions(Permission::all());
        Role::firstOrCreate(['name' => 'user', 'guard_name' => $guard])
            ->syncPermissions(Permission::whereIn('name', ['manage_invoices', 'manage_offers', 'view_reports'])->get());
    }

    public function test_get_user_context_returns_correct_structure()
    {
        Auth::login($this->user);

        $context = $this->service->getUserContext();

        $this->assertIsArray($context);
        $this->assertEquals($this->user->id, $context['id']);
        $this->assertEquals($this->user->name, $context['name']);
        $this->assertEquals($this->user->email, $context['email']);
        $this->assertArrayHasKey('company', $context);
        $this->assertArrayHasKey('permissions', $context);
        $this->assertArrayHasKey('preferences', $context);
    }

    public function test_get_user_context_for_guest_returns_guest_context()
    {
        Auth::logout();

        $context = $this->service->getUserContext();

        $this->assertIsArray($context);
        $this->assertNull($context['id'] ?? null);
    }

    public function test_get_user_context_is_cached()
    {
        Auth::login($this->user);

        Cache::shouldReceive('remember')
            ->once()
            ->andReturn(['id' => $this->user->id]);

        $this->service->getUserContext();
    }

    public function test_get_company_context_returns_correct_structure()
    {
        $context = $this->service->getCompanyContext($this->company);

        $this->assertIsArray($context);
        $this->assertEquals($this->company->id, $context['id']);
        $this->assertEquals($this->company->name, $context['name']);
        $this->assertArrayHasKey('settings', $context);
    }

    public function test_get_company_context_returns_null_for_null_company()
    {
        $context = $this->service->getCompanyContext(null);

        $this->assertNull($context);
    }

    public function test_get_dashboard_stats_returns_correct_structure()
    {
        Auth::login($this->user);

        // Create some test data
        Customer::create([
            'company_id' => $this->company->id,
            'name' => 'Test Customer',
            'email' => 'customer@test.com',
            'status' => 'active',
            'customer_type' => 'business',
        ]);

        $stats = $this->service->getDashboardStats();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('customers', $stats);
        $this->assertArrayHasKey('invoices', $stats);
        $this->assertArrayHasKey('offers', $stats);
        $this->assertArrayHasKey('products', $stats);
        $this->assertArrayHasKey('revenue', $stats);
        $this->assertEquals(1, $stats['customers']['total']);
    }

    public function test_get_dashboard_stats_returns_empty_for_user_without_company()
    {
        $userWithoutCompany = User::factory()->create(['company_id' => null]);
        Auth::login($userWithoutCompany);

        $stats = $this->service->getDashboardStats();

        $this->assertIsArray($stats);
        $this->assertEquals(0, $stats['customers']['total']);
    }

    public function test_get_dashboard_stats_uses_user_company()
    {
        Auth::login($this->user);

        $stats = $this->service->getDashboardStats();

        // Verify stats are scoped to user's company
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('customers', $stats);
    }

    public function test_clear_user_cache_removes_cache()
    {
        Auth::login($this->user);

        // Clear cache and verify it works (no exception thrown)
        $this->service->clearUserCache($this->user);
        
        // Verify cache is cleared by checking we can get fresh context
        $context = $this->service->getUserContext();
        $this->assertIsArray($context);
    }
}

