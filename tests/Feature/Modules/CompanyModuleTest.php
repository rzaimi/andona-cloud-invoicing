<?php

namespace Tests\Feature\Modules;

use App\Modules\Company\Models\Company;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CompanyModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seedRolesAndPermissions();
    }

    protected function seedRolesAndPermissions(): void
    {
        $guard = 'web';
        $permissions = ['manage_companies', 'manage_users', 'manage_settings'];
        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => $guard]);
        }
        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => $guard])
            ->syncPermissions(Permission::all());
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => $guard]);
    }

    public function test_super_admin_can_view_companies()
    {
        $superAdmin = User::factory()->create(['role' => 'admin']);
        $superAdmin->assignRole('super_admin');

        $company = Company::create([
            'name' => 'Test Company',
            'email' => 'test@company.com',
            'status' => 'active',
        ]);

        $this->actingAs($superAdmin)
            ->get(route('companies.index'))
            ->assertOk();
    }

    public function test_regular_user_cannot_view_companies()
    {
        $user = User::factory()->create(['role' => 'user']);
        $company = Company::create([
            'name' => 'Test Company',
            'email' => 'test@company.com',
            'status' => 'active',
        ]);
        $user->company_id = $company->id;
        $user->save();

        $this->actingAs($user)
            ->get(route('companies.index'))
            ->assertForbidden();
    }

    public function test_super_admin_can_create_company()
    {
        $superAdmin = User::factory()->create(['role' => 'admin']);
        $superAdmin->assignRole('super_admin');

        $this->actingAs($superAdmin)
            ->post(route('companies.store'), [
                'name' => 'New Company',
                'email' => 'new@company.com',
                'country' => 'Deutschland',
                'status' => 'active',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('companies', [
            'name' => 'New Company',
            'email' => 'new@company.com',
        ]);
    }

    public function test_super_admin_can_update_company()
    {
        $superAdmin = User::factory()->create(['role' => 'admin']);
        $superAdmin->assignRole('super_admin');

        $company = Company::create([
            'name' => 'Test Company',
            'email' => 'test@company.com',
            'status' => 'active',
        ]);

        $this->actingAs($superAdmin)
            ->put(route('companies.update', $company), [
                'name' => 'Updated Company',
                'email' => 'updated@company.com',
                'country' => 'Deutschland',
                'status' => 'active',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'name' => 'Updated Company',
        ]);
    }

    public function test_super_admin_can_delete_company()
    {
        $superAdmin = User::factory()->create(['role' => 'admin']);
        $superAdmin->assignRole('super_admin');

        $company = Company::create([
            'name' => 'Test Company',
            'email' => 'test@company.com',
            'status' => 'active',
        ]);

        $this->actingAs($superAdmin)
            ->delete(route('companies.destroy', $company))
            ->assertRedirect();

        $this->assertDatabaseMissing('companies', ['id' => $company->id]);
    }

    public function test_company_settings_can_be_retrieved()
    {
        $company = Company::create([
            'name' => 'Test Company',
            'email' => 'test@company.com',
            'status' => 'active',
        ]);

        $company->setSetting('currency', 'EUR', 'string');
        $company->setSetting('tax_rate', '0.19', 'decimal');

        $this->assertEquals('EUR', $company->getSetting('currency'));
        $this->assertEquals(0.19, $company->getSetting('tax_rate'));
    }

    public function test_company_default_settings_are_returned()
    {
        $company = Company::create([
            'name' => 'Test Company',
            'email' => 'test@company.com',
            'status' => 'active',
        ]);

        $defaults = $company->getDefaultSettings();

        $this->assertIsArray($defaults);
        $this->assertArrayHasKey('currency', $defaults);
        $this->assertArrayHasKey('tax_rate', $defaults);
    }

    public function test_wizard_validation_returns_422_without_creating_a_company()
    {
        $superAdmin = User::factory()->create(['role' => 'admin']);
        $superAdmin->assignRole('super_admin');

        $this->actingAs($superAdmin)
            ->from(route('companies.wizard.show'))
            ->withHeaders([
                'X-Inertia' => 'true',
                'X-Requested-With' => 'XMLHttpRequest',
            ])
            ->post(route('companies.wizard.complete'), [
                'company_info' => [
                    'name' => '',
                    'email' => 'not-an-email',
                ],
            ]);

        $this->assertDatabaseMissing('companies', ['email' => 'not-an-email']);
        $this->assertTrue(
            session()->has('errors'),
            'Validation should reject an empty company name and invalid email.'
        );
    }

    public function test_wizard_creates_company_with_bank_account_holder_and_admin_user()
    {
        Mail::fake();

        $superAdmin = User::factory()->create(['role' => 'admin']);
        $superAdmin->assignRole('super_admin');

        $this->actingAs($superAdmin)
            ->post(route('companies.wizard.complete'), [
                'company_info' => [
                    'name' => 'Gartenbau Nord GmbH',
                    'email' => 'info@gartenbau-nord.test',
                    'country' => 'Deutschland',
                ],
                'industry_type' => [
                    'slug' => 'gartenbau',
                    'initialize_data' => false,
                ],
                'banking_info' => [
                    'bank_name' => 'Sparkasse Berlin',
                    'iban' => 'DE89 3704 0044 0532 0130 00',
                    'bic' => 'COBADEFFXXX',
                    'account_holder' => 'Gartenbau Nord GmbH',
                ],
                'first_user' => [
                    'create_user' => true,
                    'name' => 'Anna Admin',
                    'email' => 'anna@gartenbau-nord.test',
                    'password' => 'secret123',
                    'send_welcome_email' => true,
                ],
            ])
            ->assertRedirect(route('companies.index'));

        $company = Company::where('email', 'info@gartenbau-nord.test')->first();
        $this->assertNotNull($company);
        $this->assertSame('Gartenbau Nord GmbH', $company->bank_account_holder);
        $this->assertSame('DE89370400440532013000', $company->bank_iban);
        $this->assertSame('COBADEFFXXX', $company->bank_bic);

        $admin = User::where('email', 'anna@gartenbau-nord.test')->first();
        $this->assertNotNull($admin);
        $this->assertTrue(Hash::check('secret123', $admin->password));
        $this->assertTrue($admin->hasRole('admin'));

        Mail::assertSentCount(1);
    }

    public function test_wizard_does_not_send_welcome_email_when_disabled()
    {
        Mail::fake();

        $superAdmin = User::factory()->create(['role' => 'admin']);
        $superAdmin->assignRole('super_admin');

        $this->actingAs($superAdmin)
            ->post(route('companies.wizard.complete'), [
                'company_info' => [
                    'name' => 'Ohne Mail GmbH',
                    'email' => 'info@ohne-mail.test',
                ],
                'first_user' => [
                    'create_user' => true,
                    'name' => 'Max Mustermann',
                    'email' => 'max@ohne-mail.test',
                    'password' => 'secret123',
                    'send_welcome_email' => false,
                ],
            ])
            ->assertRedirect(route('companies.index'));

        Mail::assertNothingSent();
    }
}

