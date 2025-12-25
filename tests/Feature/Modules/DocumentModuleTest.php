<?php

namespace Tests\Feature\Modules;

use App\Modules\Company\Models\Company;
use App\Modules\Customer\Models\Customer;
use App\Modules\Document\Models\Document;
use App\Modules\Invoice\Models\Invoice;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DocumentModuleTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Storage::fake('local');
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
        $userRole = Role::firstOrCreate(['name' => 'user', 'guard_name' => $guard]);
        $userRole->syncPermissions(Permission::whereIn('name', ['manage_invoices', 'manage_offers', 'view_reports'])->get());
    }

    public function test_authenticated_user_can_view_documents()
    {
        $this->actingAs($this->user)
            ->get(route('documents.index'))
            ->assertOk();
    }

    public function test_guest_cannot_view_documents()
    {
        $this->get('/settings/documents')
            ->assertRedirect('/login');
    }

    public function test_user_can_upload_document()
    {
        $file = UploadedFile::fake()->create('test.pdf', 100);

        $response = $this->actingAs($this->user)
            ->post(route('documents.store'), [
                'files' => [$file],
                'category' => 'invoice',
            ]);

        $response->assertRedirect();
        // Document name is generated from filename, so check by original_filename
        $this->assertDatabaseHas('documents', [
            'original_filename' => 'test.pdf',
            'company_id' => $this->company->id,
        ]);
    }

    public function test_user_can_link_document_to_customer()
    {
        $customer = Customer::create([
            'company_id' => $this->company->id,
            'name' => 'Test Customer',
            'email' => 'customer@test.com',
            'status' => 'active',
            'customer_type' => 'business',
        ]);

        $file = UploadedFile::fake()->create('test.pdf', 100);

        $response = $this->actingAs($this->user)
            ->post(route('documents.store'), [
                'files' => [$file],
                'category' => 'customer',
                'linkable_type' => Customer::class,
                'linkable_id' => $customer->id,
            ]);

        $response->assertRedirect();
        $document = Document::where('original_filename', 'test.pdf')
            ->where('linkable_type', Customer::class)
            ->first();
        $this->assertNotNull($document);
        $this->assertEquals($customer->id, $document->linkable_id);
    }

    public function test_user_can_link_document_to_invoice()
    {
        $customer = Customer::create([
            'company_id' => $this->company->id,
            'name' => 'Test Customer',
            'email' => 'customer@test.com',
            'status' => 'active',
            'customer_type' => 'business',
        ]);

        $invoice = Invoice::create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'user_id' => $this->user->id,
            'number' => 'RE-2024-0001',
            'status' => 'sent',
            'issue_date' => now(),
            'due_date' => now()->addDays(14),
            'subtotal' => 100.00,
            'tax_rate' => 0.19,
            'tax_amount' => 19.00,
            'total' => 119.00,
        ]);

        $file = UploadedFile::fake()->create('invoice.pdf', 100);

        $response = $this->actingAs($this->user)
            ->post(route('documents.store'), [
                'files' => [$file],
                'category' => 'invoice',
                'linkable_type' => Invoice::class,
                'linkable_id' => $invoice->id,
            ]);

        $response->assertRedirect();
        $document = Document::where('original_filename', 'invoice.pdf')
            ->where('linkable_type', Invoice::class)
            ->first();
        $this->assertNotNull($document);
    }

    public function test_user_can_delete_document()
    {
        $document = Document::create([
            'company_id' => $this->company->id,
            'name' => 'Test Document',
            'original_filename' => 'test.pdf',
            'file_path' => 'documents/test.pdf',
            'file_size' => 1000,
            'mime_type' => 'application/pdf',
            'uploaded_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('documents.destroy', $document));

        $response->assertRedirect();
        $this->assertDatabaseMissing('documents', ['id' => $document->id]);
    }

    public function test_user_cannot_view_other_company_documents()
    {
        $otherCompany = Company::create([
            'name' => 'Other Company',
            'email' => 'other@company.com',
            'status' => 'active',
        ]);

        $document = Document::create([
            'company_id' => $otherCompany->id,
            'name' => 'Other Company Document',
            'original_filename' => 'test.pdf',
            'file_path' => 'documents/test.pdf',
            'file_size' => 1000,
            'mime_type' => 'application/pdf',
            'uploaded_by' => $this->user->id,
        ]);

        $this->actingAs($this->user)
            ->get(route('documents.index'))
            ->assertOk();
        
        // Verify document is not in the list
        $page = $this->get(route('documents.index'))->viewData('page');
        $documents = $page['props']['documents']['data'] ?? [];
        $this->assertNotContains($document->id, collect($documents)->pluck('id')->toArray());
    }
}

