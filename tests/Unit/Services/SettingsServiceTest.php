<?php

namespace Tests\Unit\Services;

use App\Modules\Company\Models\Company;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SettingsServiceTest extends TestCase
{
    use RefreshDatabase;

    protected SettingsService $service;
    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SettingsService();
        $this->company = Company::create([
            'name' => 'Test Company',
            'email' => 'test@company.com',
            'status' => 'active',
        ]);
    }

    public function test_get_returns_default_value_when_setting_not_found()
    {
        $value = $this->service->get('non_existent_setting', $this->company->id, 'default_value');

        $this->assertEquals('default_value', $value);
    }

    public function test_get_returns_company_setting_when_exists()
    {
        $this->company->setSetting('test_key', 'test_value', 'string');

        $value = $this->service->get('test_key', $this->company->id);

        $this->assertEquals('test_value', $value);
    }

    public function test_set_company_creates_setting()
    {
        $this->service->setCompany('test_key', 'test_value', $this->company->id, 'string', 'Test description');

        $value = $this->service->get('test_key', $this->company->id);
        $this->assertEquals('test_value', $value);
    }

    public function test_set_company_throws_exception_for_null_company_id()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Company ID is required for company settings');

        $this->service->setCompany('test_key', 'test_value', null);
    }

    public function test_set_company_throws_exception_for_invalid_company_id()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->service->setCompany('test_key', 'test_value', 'invalid-uuid');
    }

    public function test_set_company_clears_cache()
    {
        Cache::shouldReceive('forget')
            ->with("setting.{$this->company->id}.test_key")
            ->once();

        $this->service->setCompany('test_key', 'test_value', $this->company->id);
    }

    public function test_get_casts_integer_values()
    {
        $this->company->setSetting('test_int', '123', 'integer');

        $value = $this->service->get('test_int', $this->company->id);

        $this->assertIsInt($value);
        $this->assertEquals(123, $value);
    }

    public function test_get_casts_boolean_values()
    {
        $this->company->setSetting('test_bool', '1', 'boolean');

        $value = $this->service->get('test_bool', $this->company->id);

        $this->assertIsBool($value);
        $this->assertTrue($value);
    }

    public function test_get_casts_decimal_values()
    {
        $this->company->setSetting('test_decimal', '19.5', 'decimal');

        $value = $this->service->get('test_decimal', $this->company->id);

        $this->assertIsFloat($value);
        $this->assertEquals(19.5, $value);
    }

    public function test_get_all_company_settings_returns_merged_settings()
    {
        $this->company->setSetting('currency', 'EUR', 'string');
        $this->company->setSetting('tax_rate', '0.19', 'decimal');

        $settings = $this->service->getAll($this->company->id);

        $this->assertIsArray($settings);
        $this->assertEquals('EUR', $settings['currency']);
        $this->assertEquals(0.19, $settings['tax_rate']);
    }

    public function test_get_default_settings_returns_array()
    {
        $defaults = $this->service->getDefaultSettings();

        $this->assertIsArray($defaults);
        $this->assertArrayHasKey('currency', $defaults);
        $this->assertArrayHasKey('tax_rate', $defaults);
    }
}

