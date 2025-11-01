<?php

namespace App\Modules\User\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Company\Models\Company;
use App\Modules\User\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class UserController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Super admin with manage_companies permission can see all, others only their company
        $query = User::with('company');
        if (!$user->hasPermissionTo('manage_companies')) {
            $query->where('company_id', $user->company_id);
        }

        $users = $query->paginate(15);

        return Inertia::render('users/index', [
            'users' => $users,
            'can_create' => $user->hasPermissionTo('manage_users'),
            'can_manage_companies' => $user->hasPermissionTo('manage_companies'),
        ]);
    }

    public function create()
    {
        $user = Auth::user();

        if (!$user->hasPermissionTo('manage_users')) {
            abort(403);
        }

        $companies = [];
        if ($user->hasPermissionTo('manage_companies')) {
            $companies = Company::where('status', 'active')->get();
        }

        return Inertia::render('users/create', [
            'companies' => $companies,
            'current_company_id' => $user->company_id,
            'is_super_admin' => $user->hasPermissionTo('manage_companies'),
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        if (!$user->hasPermissionTo('manage_users')) {
            abort(403);
        }

        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:user,admin',
        ];

        if ($user->hasPermissionTo('manage_companies')) {
            $rules['company_id'] = 'required|exists:companies,id';
        }

        $validated = $request->validate($rules);

        // If not super admin, use current user's company
        if (!$user->hasPermissionTo('manage_companies')) {
            $validated['company_id'] = $user->company_id;
        }

        $validated['password'] = Hash::make($validated['password']);
        $validated['status'] = 'active';

        $newUser = User::create($validated);

        // Assign Spatie role based on the role field
        $roleName = $validated['role'] === 'admin' ? 'admin' : 'user';
        $role = Role::where('name', $roleName)->first();
        if ($role) {
            $newUser->assignRole($role);
        }

        return redirect()->route('users.index')
            ->with('success', 'Benutzer wurde erfolgreich erstellt.');
    }

    public function show(User $user)
    {
        $currentUser = Auth::user();

        // Check if user can view this user
        if (!$currentUser->hasPermissionTo('manage_companies') && $user->company_id !== $currentUser->company_id) {
            abort(403);
        }

        $user->load('company');

        return Inertia::render('users/show', [
            'user' => $user,
            'can_edit' => $this->canEditUser($currentUser, $user),
        ]);
    }

    public function edit(User $user)
    {
        $currentUser = Auth::user();

        if (!$this->canEditUser($currentUser, $user)) {
            abort(403);
        }

        $companies = [];
        if ($currentUser->hasPermissionTo('manage_companies')) {
            $companies = Company::where('status', 'active')->get();
        }

        return Inertia::render('users/edit', [
            'user' => $user->load('company'),
            'companies' => $companies,
            'is_super_admin' => $currentUser->hasPermissionTo('manage_companies'),
            'available_roles' => Role::pluck('name'),
            'available_permissions' => Permission::pluck('name'),
            'assigned_roles' => $user->getRoleNames(),
            'assigned_permissions' => $user->getPermissionNames(),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $currentUser = Auth::user();

        if (!$this->canEditUser($currentUser, $user)) {
            abort(403);
        }

        $rules = [
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'role' => 'required|in:user,admin',
            'status' => 'required|in:active,inactive',
        ];

        if ($currentUser->hasPermissionTo('manage_companies')) {
            $rules['company_id'] = 'required|exists:companies,id';
        }

        if ($request->filled('password')) {
            $rules['password'] = 'string|min:8|confirmed';
        }

        // Optional role/permission arrays (names)
        $roleSync = $request->input('roles');
        $permissionSync = $request->input('permissions');

        $validated = $request->validate($rules);

        // If not super admin, don't allow company change
        if (!$currentUser->hasPermissionTo('manage_companies')) {
            unset($validated['company_id']);
        }

        if ($request->filled('password')) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        // Sync roles and permissions if provided
        if (is_array($roleSync)) {
            $user->syncRoles($roleSync);
        }
        if (is_array($permissionSync)) {
            $user->syncPermissions($permissionSync);
        }

        return redirect()->route('users.index')
            ->with('success', 'Benutzer wurde erfolgreich aktualisiert.');
    }

    public function destroy(User $user)
    {
        $currentUser = Auth::user();

        if (!$this->canEditUser($currentUser, $user)) {
            abort(403);
        }

        // Prevent self-deletion
        if ($user->id === $currentUser->id) {
            return back()->with('error', 'Sie können sich nicht selbst löschen.');
        }

        // Check if user has associated data
        $hasInvoices = $user->invoices()->exists();
        $hasOffers = $user->offers()->exists();

        if ($hasInvoices || $hasOffers) {
            return back()->with('error', 'Benutzer kann nicht gelöscht werden, da er mit Rechnungen oder Angeboten verknüpft ist.');
        }

        $user->delete();

        return redirect()->route('users.index')
            ->with('success', 'Benutzer wurde erfolgreich gelöscht.');
    }

    private function canEditUser($currentUser, $targetUser)
    {
        // Super admin with manage_companies permission can edit anyone
        if ($currentUser->hasPermissionTo('manage_companies')) {
            return true;
        }

        // Admin with manage_users permission can edit users in their company (except other admins unless it's themselves)
        if ($currentUser->hasPermissionTo('manage_users') && $targetUser->company_id === $currentUser->company_id) {
            return !$targetUser->hasRole('admin') || $targetUser->id === $currentUser->id;
        }

        // Users can only edit themselves
        return $currentUser->id === $targetUser->id;
    }
}
