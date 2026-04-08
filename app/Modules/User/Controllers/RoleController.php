<?php

namespace App\Modules\User\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::with('permissions')->orderBy('name')->get();

        // Count users per role via the pivot table (works for all Spatie versions)
        $userCounts = DB::table('model_has_roles')
            ->whereIn('role_id', $roles->pluck('id'))
            ->groupBy('role_id')
            ->select('role_id', DB::raw('count(*) as users_count'))
            ->pluck('users_count', 'role_id');

        $roles->each(function ($role) use ($userCounts) {
            $role->users_count = (int) ($userCounts[$role->id] ?? 0);
        });

        $permissions = Permission::withCount('roles')->orderBy('name')->get();

        return Inertia::render('admin/roles', [
            'roles'       => $roles,
            'permissions' => $permissions,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'permissions' => 'array',
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $role = Role::create(['name' => $data['name']]);
        if (!empty($data['permissions'])) {
            // Never allow assigning platform-level permission through the role UI
            $safe = array_values(array_diff($data['permissions'], ['manage_companies']));
            $role->syncPermissions($safe);
        }

        return back()->with('success', 'Rolle erstellt');
    }

    public function update(Request $request, Role $role)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
            'permissions' => 'array',
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        // The super_admin role always receives all permissions (managed via seeder).
        // For all other roles, strip the platform permission to prevent privilege creep.
        $permissions = $data['permissions'] ?? [];
        if ($role->name !== 'super_admin') {
            $permissions = array_values(array_diff($permissions, ['manage_companies']));
        }
        $role->update(['name' => $data['name']]);
        $role->syncPermissions($permissions);

        return back()->with('success', 'Rolle aktualisiert');
    }

    public function destroy(Role $role)
    {
        if ($role->name === 'super_admin') {
            return back()->with('error', 'Super Admin kann nicht gelöscht werden');
        }
        $role->delete();
        return back()->with('success', 'Rolle gelöscht');
    }
}


