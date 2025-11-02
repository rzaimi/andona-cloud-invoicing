<?php

namespace Tests\Feature\Settings;

use App\Modules\Company\Models\Company;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsRoutesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->company = Company::create([
            'name' => 'Test Company',
            'email' => 'test@company.com',
            'status' => 'active',
        ]);
        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
        ]);
    }

    public function test_settings_profile_route_is_accessible()
    {
        $response = $this
            ->actingAs($this->user)
            ->get('/settings/profile');

        $response->assertOk();
    }

    public function test_settings_password_route_is_accessible()
    {
        $response = $this
            ->actingAs($this->user)
            ->get('/settings/password');

        $response->assertOk();
    }

    public function test_settings_appearance_route_is_accessible()
    {
        $response = $this
            ->actingAs($this->user)
            ->get('/settings/appearance');

        $response->assertOk();
    }

    public function test_settings_company_route_is_accessible()
    {
        $response = $this
            ->actingAs($this->user)
            ->get('/settings');

        $response->assertOk();
    }

    public function test_settings_routes_require_authentication()
    {
        $routes = [
            '/settings',
            '/settings/profile',
            '/settings/password',
            '/settings/appearance',
        ];

        foreach ($routes as $route) {
            $response = $this->get($route);
            $response->assertRedirect('/login');
        }
    }

    public function test_settings_navigation_routes_exist()
    {
        // Just verify routes can be resolved without errors
        $this->actingAs($this->user);

        $this->assertNotEmpty(route('profile.edit'));
        $this->assertNotEmpty(route('password.edit'));
        $this->assertNotEmpty(route('appearance'));
        $this->assertNotEmpty(route('settings.index'));
    }
}

