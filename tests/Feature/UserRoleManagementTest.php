<?php

namespace Tests\Feature;

use App\Modules\Company\Models\Company;
use App\Modules\User\Models\User;
use App\Modules\User\Services\RoleCatalog;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRoleManagementTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected User $superAdmin;

    protected User $admin;

    protected User $member;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->company = Company::create([
            'name' => 'Rollen GmbH',
            'email' => 'rollen@example.com',
            'status' => 'active',
        ]);

        $this->superAdmin = User::factory()->create(['company_id' => $this->company->id, 'role' => 'admin']);
        $this->superAdmin->assignRole('super_admin');

        $this->admin = User::factory()->create(['company_id' => $this->company->id, 'role' => 'admin']);
        $this->admin->assignRole('admin');

        $this->member = User::factory()->create(['company_id' => $this->company->id, 'role' => 'user']);
        $this->member->assignRole('user');
    }

    private function updatePayload(User $user, array $overrides = []): array
    {
        return array_merge([
            'name' => $user->name,
            'email' => $user->email,
            'role' => 'user',
            'status' => 'active',
            'permissions' => [],
        ], $overrides);
    }

    public function test_admin_cannot_assign_the_super_admin_role(): void
    {
        $this->actingAs($this->admin)
            ->from(route('users.edit', $this->member))
            ->put(route('users.update', $this->member), $this->updatePayload($this->member, ['role' => 'super_admin']))
            ->assertSessionHasErrors('role');

        $this->assertFalse($this->member->fresh()->hasRole('super_admin'));
    }

    public function test_admin_cannot_grant_the_manage_companies_permission(): void
    {
        $this->actingAs($this->admin)
            ->from(route('users.edit', $this->member))
            ->put(route('users.update', $this->member), $this->updatePayload($this->member, [
                'permissions' => ['manage_companies'],
            ]))
            ->assertSessionHasErrors('permissions.0');

        $this->assertFalse($this->member->fresh()->hasDirectPermission('manage_companies'));
    }

    public function test_admin_cannot_edit_or_delete_a_super_admin(): void
    {
        $this->actingAs($this->admin)
            ->put(route('users.update', $this->superAdmin), $this->updatePayload($this->superAdmin))
            ->assertForbidden();

        $this->actingAs($this->admin)
            ->delete(route('users.destroy', $this->superAdmin))
            ->assertForbidden();

        $this->assertTrue($this->superAdmin->fresh()->hasRole('super_admin'));
    }

    public function test_admin_cannot_create_a_super_admin(): void
    {
        $this->actingAs($this->admin)
            ->from(route('users.create'))
            ->post(route('users.store'), [
                'name' => 'Neuer Super',
                'email' => 'neuer-super@example.com',
                'password' => 'geheim-passwort-1',
                'password_confirmation' => 'geheim-passwort-1',
                'role' => 'super_admin',
            ])
            ->assertSessionHasErrors('role');

        $this->assertNull(User::where('email', 'neuer-super@example.com')->first());
    }

    public function test_admin_sees_only_assignable_roles_and_permissions(): void
    {
        $catalog = app(RoleCatalog::class);

        $roleNames = $catalog->assignableRoleNames($this->admin);
        $this->assertNotContains('super_admin', $roleNames);
        $this->assertContains('admin', $roleNames);
        $this->assertContains('accountant', $roleNames);

        $permissionNames = $catalog->assignablePermissionNames($this->admin);
        $this->assertNotContains('manage_companies', $permissionNames);
        $this->assertContains('approve_expenses', $permissionNames);

        // Super admins see everything.
        $this->assertContains('super_admin', $catalog->assignableRoleNames($this->superAdmin));
        $this->assertContains('manage_companies', $catalog->assignablePermissionNames($this->superAdmin));
    }

    public function test_super_admin_can_assign_any_role(): void
    {
        $this->actingAs($this->superAdmin)
            ->put(route('users.update', $this->member), $this->updatePayload($this->member, [
                'role' => 'accountant',
                'company_id' => $this->company->id,
                'permissions' => ['approve_expenses'],
            ]))
            ->assertRedirect(route('users.index'));

        $fresh = $this->member->fresh();
        $this->assertTrue($fresh->hasRole('accountant'));
        $this->assertTrue($fresh->hasDirectPermission('approve_expenses'));
        // Legacy column derived from the catalog mapping.
        $this->assertSame('user', $fresh->role);
    }

    public function test_role_change_updates_legacy_column_and_replaces_old_role(): void
    {
        $this->actingAs($this->admin)
            ->put(route('users.update', $this->member), $this->updatePayload($this->member, ['role' => 'admin']))
            ->assertRedirect(route('users.index'));

        $fresh = $this->member->fresh();
        $this->assertTrue($fresh->hasRole('admin'));
        $this->assertFalse($fresh->hasRole('user'));
        $this->assertSame('admin', $fresh->role);
    }
}
