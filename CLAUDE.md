# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

**AndoBill** — a multi-tenant German invoicing/billing system (Laravel 12 + React 19 + Inertia.js). The UI language is German (prefixes like `RE-`, `AN-`, `KU-` and routes like `invoices`, `offers`, `customers` are the English-named internals; user-facing labels are German — Rechnungen, Angebote, Kunden, Mahnungen, etc.). Targets German compliance: GoBD, Mahnverfahren (dunning), E-Rechnung (XRechnung/ZUGFeRD per EN 16931).

## Common Commands

```bash
# Full dev stack (Laravel serve + queue:listen + pail logs + vite)
composer dev

# Individual parts
php artisan serve
npm run dev
npm run build            # production assets
npm run build:ssr        # build + SSR bundle

# Tests
php artisan test                       # all
./vendor/bin/phpunit                   # CI command
php artisan test --testsuite=Feature   # one suite
php artisan test --filter=TestName     # single test

# Lint / format
./vendor/bin/pint        # PHP
npm run lint             # eslint --fix
npm run format           # prettier
npm run types            # tsc --noEmit

# Ziggy routes — routes are exposed via generated file (not inlined in HTML)
npm run ziggy:generate   # regenerate resources/js/ziggy.js after route changes

# Role/permission sync (run on production after permission seeder changes)
php artisan roles:sync
```

Tests run against an in-memory SQLite DB (see `phpunit.xml`); no local DB setup needed.

## Architecture

### Modular backend layout

The app follows a **feature-module** pattern, not Laravel's default `app/Http/Controllers/*`. Each feature lives under `app/Modules/<Name>/{Controllers,Models,Policies}`:

```
app/Modules/{Calendar,Company,Customer,Dashboard,Datev,Document,Employee,
             Expense,Help,Invoice,Offer,Payment,Product,Reports,Settings,User}
```

Routes are split to match: `routes/modules/<feature>.php`, loaded from `routes/web.php`. When adding a feature, mirror this structure — don't add a controller under `app/Http/Controllers`.

Model→Policy bindings are registered manually in `app/Providers/AuthServiceProvider.php` (not auto-discovered because models aren't in `app/Models`). Any new policy must be added there.

### Multi-tenancy (critical)

Data is scoped by `company_id`. **Never query tenant-owned models without filtering by company.** The pattern:

- Every controller extends `App\Http\Controllers\Controller`, which provides `$this->getEffectiveCompanyId()`.
- For regular users, that returns `$user->company_id`.
- For users with the `manage_companies` permission (super admins), it returns the **session-selected** company (`selected_company_id`), falling back to the default company. Super admins can switch companies via `CompanyContextController`.
- Tenant models expose a `forCompany($id)` scope — use it in queries.
- The same resolution logic is duplicated in `Controller.php`, `ContextService.php`, and `HandleInertiaRequests.php`. If tenancy rules change, update all three.

### Auth / roles / permissions

- Uses **Spatie laravel-permission**. Roles: `super-admin`, `admin`, `user`, `employee`. Permissions are seeded by `RolesAndPermissionsSeeder`; run `php artisan roles:sync` on production after changing it.
- **Employees** (role `employee`) are hard-redirected by `RedirectEmployeeToPortal` middleware to `/portal/*` — they cannot access the main app. This middleware is applied to the entire authenticated web group.
- Middleware aliases (see `bootstrap/app.php`): `session.timeout`, `role`, `permission`, `role_or_permission`, `employee.portal.only`.
- **Don't read `$user->role` for authorization** — that's a legacy string column. Use `hasPermissionTo()` / `hasRole()` / policies.

### Frontend (Inertia + React)

- Entry: `resources/js/app.tsx` → `createInertiaApp` resolves pages via glob `./pages/**/*.tsx`. Page name in `Inertia::render('invoices/create', …)` maps to `resources/js/pages/invoices/create.tsx`.
- Routes available to the frontend via **Ziggy**, loaded from generated `resources/js/ziggy.js` (not from an Inertia prop) — regenerate after route changes with `npm run ziggy:generate`.
- Shared Inertia props (see `HandleInertiaRequests`): `auth.user` (with `roles`, `permissions`, and `company.settings`), `available_companies` (super-admins only), `flash`, `csrfToken`, `sidebarOpen`. Settings payload is trimmed to essentials to keep page props small.
- UI: Radix primitives + Tailwind v4 (via `@tailwindcss/vite`), shadcn-style components under `resources/js/components/ui`.

### Services and shared logic

- `ContextService` (singleton) — canonical source for user/company context + dashboard stats, with 5-min cache keyed on user/company ID. Call `clearUserCache()` after mutating user or company state.
- `SettingsService` — key/value settings per company (and some global). Used to resolve number formats, tax rates, payment methods, etc.
- `NumberFormatService` — generates invoice/offer/customer numbers from templates like `RE-{YYYY}-{####}`.
- `ERechnungService` — XRechnung/ZUGFeRD generation via `horstoeko/zugferd`.
- `FormattingService`, `NumberFormatService`, `ExpenseReportService` — formatting and reporting helpers.

### Invoices — GoBD rules

Invoices have state-based edit locks (GoBD compliance):

- Only **draft** invoices are editable. `sent`/`paid`/`overdue` are locked.
- Use **Stornorechnung** (correction) flow via `InvoiceController::createCorrection` — don't mutate sent invoices.
- All state changes are written to `invoice_audit_logs` (see `InvoiceAuditLog` model).
- Invoice types: `standard`, `abschlagsrechnung`, `schlussrechnung`, `nachtragsrechnung`, `korrekturrechnung` (constants on `Invoice` model).
- Reminder levels (Mahnverfahren): constants `REMINDER_NONE` … `REMINDER_INKASSO` on `Invoice`.
- Items carry **per-item tax rates** (`invoice_items.tax_rate`, same for `offer_items`) — when summing, don't assume a single invoice-level rate.

### Scheduled jobs

`routes/console.php` schedules `reminders:send` daily at 09:00. On a new host, ensure `php artisan schedule:work` (dev) or a system cron entry (`* * * * * php artisan schedule:run`) is running.

### Storage

Tenant-scoped documents live under per-company private disk paths — see `MoveDocumentsToPrivateStorage` and `MigrateStorageToTenantStructure` commands for the layout if you need to touch storage code.

## Conventions

- IDs are UUIDs (`HasUuids` trait on tenant models). Don't type route/model IDs as `int`.
- Route module files under `routes/modules/` are loaded in a specific order inside `routes/web.php` (company `settings.php` before user `settings.php`) — keep new route files in the group and mind the ordering when paths overlap.
- Prefer the `inertia()` helper on the base `Controller` when you want the standard `user` prop merged in.
- `.env.example` contains PRODUCTION comments flagging values that must change (e.g., `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`, `LOG_LEVEL=error`, `DEPLOY_TOKEN`) — respect those when editing deployment-adjacent code.

## Further reading

`README.md` covers installation/setup; `COMPLETE_DOCUMENTATION.md` is the exhaustive feature/implementation reference (security audits, E-Rechnung details, deployment notes). `SETTINGS_FIELDS_VALIDATION.md` lists settings field constraints.
