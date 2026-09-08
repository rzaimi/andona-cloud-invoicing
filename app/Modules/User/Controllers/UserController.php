<?php

namespace App\Modules\User\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Company\Models\Company;
use App\Modules\Document\Models\Document;
use App\Modules\User\Models\User;
use App\Modules\User\Services\RoleCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $search = $request->input('search', '');

        // Always scope to the effective company so super-admins see users
        // for the company they have currently selected, not every user in
        // the system.  They can switch context via the company selector.
        $companyId = $this->getEffectiveCompanyId();

        $query = User::with('company')
            ->where('company_id', $companyId)
            ->when($search, fn ($q) => $q->where(function ($inner) use ($search) {
                $inner->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            }))
            ->orderBy('name');

        $paginated = $query->paginate(15)->withQueryString();

        // Attach per-row permission flags so the frontend can show/hide actions correctly
        $paginated->getCollection()->transform(function ($u) use ($user) {
            $canEdit = $this->canEditUser($user, $u);
            $u->can_edit = $canEdit;
            $u->can_delete = $canEdit && $u->id !== $user->id;

            return $u;
        });

        return Inertia::render('users/index', [
            'users' => $paginated,
            'search' => $search,
            'can_create' => $user->hasPermissionTo('manage_users'),
            'can_manage_companies' => $user->hasPermissionTo('manage_companies'),
        ]);
    }

    public function create()
    {
        $this->authorize('create', User::class);
        $user = Auth::user();

        $companies = [];
        if ($user->hasPermissionTo('manage_companies')) {
            $companies = Company::where('status', 'active')->get();
        }

        return Inertia::render('users/create', [
            'companies' => $companies,
            'current_company_id' => $user->company_id,
            'is_super_admin' => $user->hasPermissionTo('manage_companies'),
            'role_options' => app(RoleCatalog::class)->assignableRoles($user),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', User::class);
        $user = Auth::user();

        $catalog = app(RoleCatalog::class);
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            // Only roles the current editor may assign — a company admin can
            // never create a super admin.
            'role' => ['required', Rule::in($catalog->assignableRoleNames($user))],
            'staff_number' => 'nullable|string|max:50',
            'department' => 'nullable|string|max:100',
            'job_title' => 'nullable|string|max:100',
        ];

        if ($user->hasPermissionTo('manage_companies')) {
            $rules['company_id'] = 'required|exists:companies,id';
        }

        $validated = $request->validate($rules);

        // If not super admin, use current user's company
        if (! $user->hasPermissionTo('manage_companies')) {
            $validated['company_id'] = $user->company_id;
        }

        $validated['password'] = Hash::make($validated['password']);
        $validated['status'] = 'active';

        // The Spatie role is authoritative; the legacy users.role column is
        // derived from it (older code paths like the portal redirect read it).
        $spatieRole = $validated['role'];
        $validated['role'] = $catalog->legacyColumnFor($spatieRole);

        $newUser = User::create($validated);
        $newUser->assignRole($spatieRole);

        return redirect()->route('users.index')
            ->with('success', 'Benutzer wurde erfolgreich erstellt.');
    }

    public function show(User $user)
    {
        $this->authorize('view', $user);
        $currentUser = Auth::user();

        $user->load('company');

        return Inertia::render('users/show', [
            'user' => $user,
            'can_edit' => $this->canEditUser($currentUser, $user),
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ]);
    }

    public function edit(User $user)
    {
        $this->authorize('update', $user);
        $currentUser = Auth::user();

        $companies = [];
        if ($currentUser->hasPermissionTo('manage_companies')) {
            $companies = Company::where('status', 'active')->get();
        }

        $catalog = app(RoleCatalog::class);

        return Inertia::render('users/edit', [
            'user' => $user->load('company'),
            'companies' => $companies,
            'is_super_admin' => $currentUser->hasPermissionTo('manage_companies'),
            // Catalog-driven: the editor only ever sees what they may assign.
            'role_options' => $catalog->assignableRoles($currentUser),
            'permission_groups' => $catalog->assignablePermissionGroups($currentUser),
            'current_role' => $catalog->primaryRole($user),
            'assigned_permissions' => $user->getPermissionNames(),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $this->authorize('update', $user);
        $currentUser = Auth::user();

        $catalog = app(RoleCatalog::class);
        $rules = [
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            // Only roles the editor may assign — assigning super_admin (or any
            // unknown role) is a validation error, not a silent strip.
            'role' => ['required', Rule::in($catalog->assignableRoleNames($currentUser))],
            'status' => 'required|in:active,inactive',
            'permissions' => 'sometimes|array',
            'permissions.*' => [Rule::in($catalog->assignablePermissionNames($currentUser))],
            'staff_number' => 'nullable|string|max:50',
            'department' => 'nullable|string|max:100',
            'job_title' => 'nullable|string|max:100',
        ];

        if ($currentUser->hasPermissionTo('manage_companies')) {
            $rules['company_id'] = 'required|exists:companies,id';
        }

        if ($request->filled('password')) {
            $rules['password'] = 'string|min:8|confirmed';
        }

        $validated = $request->validate($rules, [
            'role.in' => 'Diese Rolle dürfen Sie nicht vergeben.',
            'permissions.*.in' => 'Mindestens eine Berechtigung dürfen Sie nicht vergeben.',
        ]);

        // If not super admin, don't allow company change
        if (! $currentUser->hasPermissionTo('manage_companies')) {
            unset($validated['company_id']);
        }

        if ($request->filled('password')) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        // The Spatie role is authoritative; the legacy column is derived.
        $spatieRole = $validated['role'];
        $validated['role'] = $catalog->legacyColumnFor($spatieRole);
        $permissions = $validated['permissions'] ?? null;
        unset($validated['permissions']);

        $user->update($validated);
        $user->syncRoles([$spatieRole]);

        if (is_array($permissions)) {
            $user->syncPermissions($permissions);
        }

        return redirect()->route('users.index')
            ->with('success', 'Benutzer wurde erfolgreich aktualisiert.');
    }

    public function destroy(User $user)
    {
        $this->authorize('delete', $user);
        $currentUser = Auth::user();

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

    public function documents(Request $request, User $user)
    {
        $this->authorize('update', $user);
        $currentUser = Auth::user();

        $companyId = $currentUser->hasPermissionTo('manage_companies')
            ? $user->company_id
            : $currentUser->company_id;

        $documents = Document::query()
            ->where('company_id', $companyId)
            ->where('linkable_type', User::class)
            ->where('linkable_id', $user->id)
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('users/documents', [
            'employee' => $user->only('id', 'name', 'email', 'staff_number', 'department', 'job_title'),
            'documents' => $documents,
        ]);
    }

    private function canEditUser($currentUser, $targetUser)
    {
        // Super admin with manage_companies permission can edit anyone
        if ($currentUser->hasPermissionTo('manage_companies')) {
            return true;
        }

        // Super admins are untouchable for everyone below super admin.
        if ($targetUser->hasRole('super_admin') || $targetUser->hasDirectPermission('manage_companies')) {
            return false;
        }

        // Admin with manage_users permission can edit users in their company (except other admins unless it's themselves)
        if ($currentUser->hasPermissionTo('manage_users') && $targetUser->company_id === $currentUser->company_id) {
            return ! $targetUser->hasRole('admin') || $targetUser->id === $currentUser->id;
        }

        // Users can only edit themselves
        return $currentUser->id === $targetUser->id;
    }
}
