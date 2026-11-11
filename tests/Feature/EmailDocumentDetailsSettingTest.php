<?php

namespace Tests\Feature;

use App\Modules\Company\Models\Company;
use App\Modules\User\Models\User;
use Tests\TestCase;

class EmailDocumentDetailsSettingTest extends TestCase
{
    public function test_the_preview_hides_the_details_table_when_the_switch_is_off(): void
    {
        $this->seedRolesAndPermissions();

        $company = Company::create([
            'name' => 'Preview GmbH',
            'email' => 'preview@example.com',
            'status' => 'active',
        ]);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'role' => 'user',
        ]);
        $user->assignRole('user');

        $this->actingAs($user)
            ->get(route('settings.emails.preview.invoice-sent', ['show_details' => 0]))
            ->assertOk()
            ->assertDontSee('Rechnungsnummer', false)
            ->assertSee('anbei erhalten Sie die Rechnung', false);

        $this->actingAs($user)
            ->get(route('settings.emails.preview.offer-sent', ['show_details' => 0]))
            ->assertOk()
            ->assertDontSee('Angebotsnummer', false)
            ->assertSee('folgendes Angebot', false);
    }

    public function test_turning_the_switch_off_is_stored_without_the_smtp_form(): void
    {
        $this->seedRolesAndPermissions();

        $company = Company::create([
            'name' => 'Switch GmbH',
            'email' => 'switch@example.com',
            'status' => 'active',
        ]);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'role' => 'user',
        ]);
        $user->assignRole('user');

        $this->actingAs($user)
            ->from(route('settings.index', ['tab' => 'email']))
            ->post(route('settings.email.document-details'), [
                'email_show_document_details' => false,
            ])
            ->assertRedirect();

        $this->assertSame('0', $company->fresh()->getSetting('email_show_document_details'));
    }
}
