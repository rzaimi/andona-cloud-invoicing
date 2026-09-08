<?php

namespace App\Modules\User\Services;

use App\Modules\User\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Single source of truth for the role/permission model shown in the user
 * management UI and enforced on assignment.
 *
 * Hierarchy: only super admins (manage_companies) may assign the super_admin
 * role or platform permissions, and only they may touch users who hold them.
 * Company admins manage the tenant-level roles and permissions below.
 */
class RoleCatalog
{
    public const SUPER_ADMIN = 'super_admin';

    /**
     * Ordered by rank — the first role a user holds is their primary role.
     * `legacy` is the value written to the users.role column, which older
     * code paths (e.g. the employee portal redirect) still read.
     */
    private const ROLES = [
        'super_admin' => [
            'label' => 'Super Admin',
            'description' => 'Plattform-Administrator: verwaltet alle Firmen und Systemeinstellungen.',
            'legacy' => 'admin',
            'super_only' => true,
        ],
        'admin' => [
            'label' => 'Administrator',
            'description' => 'Verwaltet Benutzer, Einstellungen und alle Belege dieser Firma.',
            'legacy' => 'admin',
            'super_only' => false,
        ],
        'accountant' => [
            'label' => 'Buchhaltung',
            'description' => 'Erfasst und bearbeitet Ausgaben und Belege, exportiert für den Steuerberater.',
            'legacy' => 'user',
            'super_only' => false,
        ],
        'approver' => [
            'label' => 'Freigeber',
            'description' => 'Gibt Ausgaben frei oder lehnt sie ab.',
            'legacy' => 'user',
            'super_only' => false,
        ],
        'auditor' => [
            'label' => 'Prüfer',
            'description' => 'Lesender Zugriff zur Prüfung von Belegen und Protokollen.',
            'legacy' => 'user',
            'super_only' => false,
        ],
        'user' => [
            'label' => 'Benutzer',
            'description' => 'Erstellt und verwaltet Rechnungen, Angebote und Produkte.',
            'legacy' => 'user',
            'super_only' => false,
        ],
        'employee' => [
            'label' => 'Mitarbeiter',
            'description' => 'Zugriff nur auf das Mitarbeiterportal (eigene Dokumente).',
            'legacy' => 'employee',
            'super_only' => false,
        ],
    ];

    private const PERMISSIONS = [
        // Belege & Finanzen
        'manage_invoices' => ['label' => 'Rechnungen verwalten', 'group' => 'Belege & Finanzen', 'super_only' => false],
        'manage_offers' => ['label' => 'Angebote verwalten', 'group' => 'Belege & Finanzen', 'super_only' => false],
        'manage_products' => ['label' => 'Produkte verwalten', 'group' => 'Belege & Finanzen', 'super_only' => false],
        'create_stornorechnung' => ['label' => 'Stornorechnungen erstellen', 'group' => 'Belege & Finanzen', 'super_only' => false],
        'view_reports' => ['label' => 'Berichte anzeigen', 'group' => 'Belege & Finanzen', 'super_only' => false],
        // Ausgaben
        'approve_expenses' => ['label' => 'Ausgaben freigeben', 'group' => 'Ausgaben', 'super_only' => false],
        'export_expenses' => ['label' => 'Ausgaben exportieren', 'group' => 'Ausgaben', 'super_only' => false],
        // Verwaltung
        'manage_users' => ['label' => 'Benutzer verwalten', 'group' => 'Verwaltung', 'super_only' => false],
        'manage_settings' => ['label' => 'Einstellungen verwalten', 'group' => 'Verwaltung', 'super_only' => false],
        'manage_employee_documents' => ['label' => 'Mitarbeiterdokumente verwalten', 'group' => 'Verwaltung', 'super_only' => false],
        'view_own_documents' => ['label' => 'Eigene Dokumente einsehen', 'group' => 'Verwaltung', 'super_only' => false],
        // Plattform — never assignable by company admins
        'manage_companies' => ['label' => 'Firmen verwalten (Plattform)', 'group' => 'Plattform', 'super_only' => true],
    ];

    /**
     * Roles the editor may assign, as [{name, label, description}] —
     * restricted to roles that actually exist in the database.
     */
    public function assignableRoles(User $editor): array
    {
        $isSuper = $editor->hasPermissionTo('manage_companies');
        $existing = Role::where('guard_name', $this->guard())->pluck('name')->all();

        $roles = [];
        foreach (self::ROLES as $name => $meta) {
            if (($meta['super_only'] && ! $isSuper) || ! in_array($name, $existing, true)) {
                continue;
            }
            $roles[] = ['name' => $name, 'label' => $meta['label'], 'description' => $meta['description']];
        }

        return $roles;
    }

    /**
     * Permissions the editor may grant, grouped for the UI:
     * [{group, items: [{name, label}]}].
     */
    public function assignablePermissionGroups(User $editor): array
    {
        $isSuper = $editor->hasPermissionTo('manage_companies');
        $existing = Permission::where('guard_name', $this->guard())->pluck('name')->all();

        $groups = [];
        foreach (self::PERMISSIONS as $name => $meta) {
            if (($meta['super_only'] && ! $isSuper) || ! in_array($name, $existing, true)) {
                continue;
            }
            $groups[$meta['group']][] = ['name' => $name, 'label' => $meta['label']];
        }

        return collect($groups)
            ->map(fn ($items, $group) => ['group' => $group, 'items' => $items])
            ->values()
            ->all();
    }

    /** @return list<string> */
    public function assignableRoleNames(User $editor): array
    {
        return array_column($this->assignableRoles($editor), 'name');
    }

    /** @return list<string> */
    public function assignablePermissionNames(User $editor): array
    {
        return collect($this->assignablePermissionGroups($editor))
            ->flatMap(fn ($group) => array_column($group['items'], 'name'))
            ->all();
    }

    /**
     * The users.role column value for a catalog role.
     */
    public function legacyColumnFor(string $role): string
    {
        return self::ROLES[$role]['legacy'] ?? 'user';
    }

    /**
     * A user's primary role: the highest-ranked Spatie role they hold,
     * falling back to the legacy column.
     */
    public function primaryRole(User $user): string
    {
        $held = $user->getRoleNames()->all();

        foreach (array_keys(self::ROLES) as $name) {
            if (in_array($name, $held, true)) {
                return $name;
            }
        }

        return in_array($user->role, array_keys(self::ROLES), true) ? $user->role : 'user';
    }

    /**
     * Users only super admins may manage: super_admin holders and anyone
     * directly granted a platform permission.
     */
    public function isProtected(User $user): bool
    {
        return $user->hasRole(self::SUPER_ADMIN)
            || $user->hasDirectPermission('manage_companies');
    }

    private function guard(): string
    {
        return (string) config('auth.defaults.guard', 'web');
    }
}
