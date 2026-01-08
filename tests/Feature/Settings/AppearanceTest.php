<?php

namespace Tests\Feature\Settings;

use App\Modules\Company\Models\Company;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppearanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seedRolesAndPermissions();
    }

    public function test_appearance_page_is_displayed()
    {
        $company = Company::create([
            'name' => 'Test Company',
            'email' => 'test@company.com',
            'status' => 'active',
        ]);
        
        $user = User::factory()->create([
            'company_id' => $company->id,
        ]);
        $user->assignRole('user');
        $user->assignRole('user');

        $response = $this
            ->actingAs($user)
            ->get('/settings/appearance');

        // The route now redirects to the unified settings page with appearance tab
        $response->assertRedirect(route('settings.index', ['tab' => 'appearance']));
    }

    public function test_appearance_page_requires_authentication()
    {
        $response = $this->get('/settings/appearance');

        $response->assertRedirect('/login');
    }

    public function test_appearance_page_renders_inertia_component()
    {
        $company = Company::create([
            'name' => 'Test Company',
            'email' => 'test@company.com',
            'status' => 'active',
        ]);
        
        $user = User::factory()->create([
            'company_id' => $company->id,
        ]);
        $user->assignRole('user');

        // Follow the redirect to the unified settings page
        $response = $this
            ->actingAs($user)
            ->get('/settings/appearance');

        // Follow redirect and check it renders the unified settings page with appearance tab
        $response = $this->followRedirects($response);
        $response->assertInertia(fn ($page) => 
            $page->component('settings/index')
                ->where('activeTab', 'appearance')
        );
    }
}

