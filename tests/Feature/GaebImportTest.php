<?php

namespace Tests\Feature;

use App\Modules\Company\Models\Company;
use App\Modules\User\Models\User;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class GaebImportTest extends TestCase
{
    public function test_import_is_refused_until_the_company_enables_it(): void
    {
        [$company, $user] = $this->companyUser();

        $this->actingAs($user)
            ->post('/gaeb/positions', [
                'file' => $this->gaebFile(),
            ])
            ->assertForbidden();

        $this->assertFalse($company->gaebImportEnabled());
    }

    public function test_enabled_company_receives_only_the_positions(): void
    {
        [$company, $user] = $this->companyUser();
        $company->setSetting('gaeb_import_enabled', '1', 'string');

        $this->actingAs($user)
            ->post('/gaeb/positions', [
                'file' => $this->gaebFile(),
            ])
            ->assertOk()
            ->assertJsonPath('project', '26-02')
            ->assertJsonCount(4, 'positions')
            ->assertJsonPath('positions.0.description', "01.10 Abbruch von Bestandsfliesen\n\nAbbruch und Entsorgung des Bestandsfliesenbelages")
            ->assertJsonPath('positions.0.quantity', 29.5)
            ->assertJsonPath('positions.0.unit', 'm²')
            ->assertJsonPath('positions.0.unit_price', 0)
            ->assertJsonMissing(['name' => 'Hinweis Untergrund']);
    }

    public function test_the_invoice_form_receives_the_flag(): void
    {
        [$company, $user] = $this->companyUser();
        $company->setSetting('gaeb_import_enabled', '1', 'string');

        $this->actingAs($user)
            ->get('/invoices/create')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('auth.user.company.settings.gaeb_import_enabled', true));
    }

    public function test_a_company_without_invoices_or_offers_cannot_import(): void
    {
        [$company, $user] = $this->companyUser();
        $company->update(['enabled_modules' => ['expenses']]);
        $company->setSetting('gaeb_import_enabled', '1', 'string');

        $this->actingAs($user)
            ->post('/gaeb/positions', [
                'file' => $this->gaebFile(),
            ])
            ->assertForbidden();
    }

    public function test_non_gaeb_content_is_rejected(): void
    {
        [$company, $user] = $this->companyUser();
        $company->setSetting('gaeb_import_enabled', '1', 'string');

        $this->actingAs($user)
            ->post('/gaeb/positions', [
                'file' => UploadedFile::fake()->createWithContent('notes.X81', '<html>kein gaeb</html>'),
            ])
            ->assertSessionHasErrors('file');
    }

    public function test_settings_page_can_turn_the_import_on(): void
    {
        [$company, $user] = $this->companyUser();

        $this->actingAs($user)
            ->post(route('settings.gaeb.update'), ['gaeb_import_enabled' => true])
            ->assertRedirect(route('settings.index', ['tab' => 'gaeb']));

        $this->assertTrue($company->fresh()->gaebImportEnabled());

        $this->actingAs($user)
            ->get('/settings?tab=gaeb')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('gaebImportEnabled', true)
                ->where('activeTab', 'gaeb')
                ->where('auth.user.company.settings.gaeb_import_enabled', true));
    }

    public function test_super_admin_can_enable_and_disable_the_import(): void
    {
        $this->seedRolesAndPermissions();

        $company = Company::create([
            'name' => 'GAEB GmbH',
            'email' => 'gaeb-admin@example.com',
            'status' => 'active',
        ]);
        $superAdmin = User::factory()->create([
            'company_id' => $company->id,
            'role' => 'admin',
        ]);
        $superAdmin->assignRole('super_admin');

        $this->actingAs($superAdmin)
            ->put(route('companies.update', $company), [
                'name' => $company->name,
                'email' => $company->email,
                'country' => 'Deutschland',
                'status' => 'active',
                'gaeb_import_enabled' => true,
            ])
            ->assertRedirect();

        $this->assertTrue($company->fresh()->gaebImportEnabled());

        $this->actingAs($superAdmin)
            ->put(route('companies.update', $company), [
                'name' => $company->name,
                'email' => $company->email,
                'country' => 'Deutschland',
                'status' => 'active',
                'gaeb_import_enabled' => false,
            ])
            ->assertRedirect();

        $this->assertFalse($company->fresh()->gaebImportEnabled());
    }

    /**
     * @return array{0: Company, 1: User}
     */
    private function companyUser(): array
    {
        $this->seedRolesAndPermissions();

        $company = Company::create([
            'name' => 'Fliesen GmbH',
            'email' => 'fliesen@example.com',
            'status' => 'active',
        ]);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'role' => 'user',
        ]);
        $user->assignRole('user');

        return [$company, $user];
    }

    private function gaebFile(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            'leistungsverzeichnis.X81',
            file_get_contents(base_path('tests/fixtures/gaeb-sample.x81'))
        );
    }
}
