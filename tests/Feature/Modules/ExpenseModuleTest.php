<?php

namespace Tests\Feature\Modules;

use App\Modules\Company\Models\Company;
use App\Modules\Expense\Models\Expense;
use App\Modules\Expense\Models\ExpenseCategory;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseModuleTest extends TestCase
{
    use RefreshDatabase;

    protected $company;
    protected $user;
    protected $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seedRolesAndPermissions();

        // Create company without bank attributes to avoid settings creation issues
        $this->company = Company::create([
            'name' => 'Test Company',
            'email' => 'test@example.com',
            'status' => 'active',
        ]);

        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
        ]);
        $this->user->assignRole('admin');

        $this->category = ExpenseCategory::create([
            'company_id' => $this->company->id,
            'name' => 'Test Category',
        ]);
    }

    public function test_user_can_view_expenses_index()
    {
        $this->actingAs($this->user);

        Expense::create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'title' => 'Test Expense',
            'amount' => 100.00,
            'vat_rate' => 0.19,
            'expense_date' => now(),
        ]);

        $response = $this->get('/expenses');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('expenses/index')
            ->has('expenses.data', 1)
        );
    }

    public function test_user_can_create_expense()
    {
        $this->actingAs($this->user);

        $expenseData = [
            'category_id' => $this->category->id,
            'title' => 'New Expense',
            'description' => 'Test description',
            'amount' => 200.00,
            'vat_rate' => 0.19,
            'expense_date' => now()->format('Y-m-d'),
            'payment_method' => 'Überweisung',
        ];

        $response = $this->post('/expenses', $expenseData);

        $response->assertRedirect('/expenses');
        $this->assertDatabaseHas('expenses', [
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'title' => 'New Expense',
            'amount' => 200.00,
        ]);
    }

    public function test_expense_calculates_vat_and_net_amount()
    {
        $this->actingAs($this->user);

        $expense = Expense::create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'title' => 'Test Expense',
            'amount' => 100.00,
            'vat_rate' => 0.19,
            'expense_date' => now(),
        ]);

        $this->assertEquals(19.00, $expense->vat_amount);
        // amount is gross (100.00), so net = gross - vat = 100 - 19 = 81.00
        $this->assertEquals(81.00, $expense->net_amount);
    }

    public function test_user_can_update_expense()
    {
        $this->actingAs($this->user);

        $expense = Expense::create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'title' => 'Original Title',
            'amount' => 100.00,
            'vat_rate' => 0.19,
            'expense_date' => now(),
        ]);

        $response = $this->put("/expenses/{$expense->id}", [
            'title' => 'Updated Title',
            'amount' => 150.00,
            'vat_rate' => 0.19,
            'expense_date' => now()->format('Y-m-d'),
        ]);

        $response->assertRedirect('/expenses');
        $this->assertDatabaseHas('expenses', [
            'id' => $expense->id,
            'title' => 'Updated Title',
            'amount' => 150.00,
        ]);
    }

    public function test_user_can_delete_expense()
    {
        $this->actingAs($this->user);

        $expense = Expense::create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'title' => 'To Delete',
            'amount' => 100.00,
            'vat_rate' => 0.19,
            'expense_date' => now(),
        ]);

        $response = $this->delete("/expenses/{$expense->id}");

        $response->assertRedirect('/expenses');
        $this->assertDatabaseMissing('expenses', ['id' => $expense->id]);
    }

    public function test_user_can_create_expense_category()
    {
        $this->actingAs($this->user);

        $response = $this->post('/expenses/categories', [
            'name' => 'New Category',
        ]);

        $response->assertRedirect('/expenses/categories');
        $this->assertDatabaseHas('expense_categories', [
            'company_id' => $this->company->id,
            'name' => 'New Category',
        ]);
    }

    public function test_user_cannot_create_duplicate_category()
    {
        $this->actingAs($this->user);

        ExpenseCategory::create([
            'company_id' => $this->company->id,
            'name' => 'Existing Category',
        ]);

        $response = $this->post('/expenses/categories', [
            'name' => 'Existing Category',
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_user_cannot_delete_category_with_expenses()
    {
        $this->actingAs($this->user);

        $category = ExpenseCategory::create([
            'company_id' => $this->company->id,
            'name' => 'Category with Expenses',
        ]);

        Expense::create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'category_id' => $category->id,
            'title' => 'Test',
            'amount' => 100.00,
            'vat_rate' => 0.19,
            'expense_date' => now(),
        ]);

        $response = $this->delete("/expenses/categories/{$category->id}");

        $response->assertSessionHasErrors('error');
        $this->assertDatabaseHas('expense_categories', ['id' => $category->id]);
    }

    public function test_expenses_are_isolated_by_company()
    {
        // Create company without bank attributes to avoid settings creation issues
        $company2 = Company::create([
            'name' => 'Company 2',
            'email' => 'company2@example.com',
            'status' => 'active',
        ]);
        $user2 = User::factory()->create(['company_id' => $company2->id]);
        $user2->assignRole('admin');

        Expense::create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'title' => 'Company 1 Expense',
            'amount' => 100.00,
            'vat_rate' => 0.19,
            'expense_date' => now(),
        ]);

        Expense::create([
            'company_id' => $company2->id,
            'user_id' => $user2->id,
            'title' => 'Company 2 Expense',
            'amount' => 200.00,
            'vat_rate' => 0.19,
            'expense_date' => now(),
        ]);

        $this->actingAs($this->user);
        $response = $this->get('/expenses');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('expenses/index')
            ->has('expenses.data', 1)
            ->where('expenses.data.0.title', 'Company 1 Expense')
        );
    }
}

