<?php

namespace App\Modules\User\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    public function index()
    {
        $permissions = Permission::with('roles')->orderBy('name')->get();

        return Inertia::render('admin/permissions', [
            'permissions' => $permissions->map(fn($p) => [
                'id'    => $p->id,
                'name'  => $p->name,
                'roles' => $p->roles->pluck('name'),
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:permissions,name',
        ]);

        Permission::create(['name' => $data['name']]);
        return back()->with('success', 'Berechtigung erstellt');
    }

    public function destroy(Permission $permission)
    {
        $permission->delete();
        return back()->with('success', 'Berechtigung gelöscht');
    }
}


