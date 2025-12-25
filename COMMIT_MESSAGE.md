feat: Add comprehensive test suite for modules and services

Add extensive test coverage for all modules, services, and multi-tenancy functionality.
All tests use RefreshDatabase trait which automatically handles database transactions
for clean test state with automatic rollback.

## Test Coverage Added

### Services Tests (27 tests)
- ContextServiceTest: User context, company context, dashboard stats, caching
- SettingsServiceTest: Company/global settings, value casting, cache management
- ERechnungServiceTest: XRechnung and ZUGFeRD generation

### Module Tests
- CompanyModuleTest: CRUD operations, settings management, super admin access
- DashboardModuleTest: Dashboard access, statistics, recent items, alerts
- DocumentModuleTest: Document upload, linking, deletion, multi-tenancy
- CalendarModuleTest: Calendar access, invoice due dates, offer expiry
- ReportsModuleTest: Reports access, revenue, customer, tax reports

### Multi-Tenancy Tests (39 tests)
- Data isolation between companies
- Policy enforcement
- Company switching for super admins
- CRUD operations across different companies

### Settings Tests
- ProfileUpdateTest: Profile management
- PasswordUpdateTest: Password updates
- CompanySettingsTest: Company settings management
- AppearanceTest: Appearance settings

## Test Infrastructure Improvements

- Added `seedRolesAndPermissions()` helper method to TestCase base class
- All tests properly seed roles and permissions before execution
- Added `withoutVite()` to all feature tests to skip frontend asset compilation
- Fixed permission imports in DocumentModuleTest
- Updated test data to match actual validation rules

## Test Results

- ✅ 131 tests passing
- ⏭️ 6 tests skipped (password reset and registration routes disabled)
- ❌ 0 tests failing

## Files Changed

### New Test Files
- tests/Unit/Services/ContextServiceTest.php
- tests/Unit/Services/SettingsServiceTest.php
- tests/Unit/Services/ERechnungServiceTest.php
- tests/Feature/Modules/CompanyModuleTest.php
- tests/Feature/Modules/DashboardModuleTest.php
- tests/Feature/Modules/DocumentModuleTest.php
- tests/Feature/Modules/CalendarModuleTest.php
- tests/Feature/Modules/ReportsModuleTest.php
- tests/TEST_COVERAGE_SUMMARY.md
- tests/TEST_STATUS.md

### Updated Test Files
- tests/TestCase.php: Added seedRolesAndPermissions() helper
- tests/Feature/Auth/*: Added permission seeding and company setup
- tests/Feature/Settings/*: Added permission seeding and fixed validation
- tests/Feature/DashboardTest.php: Added permission seeding
- tests/Feature/Modules/*: Added permission seeding and role assignment

## Notes

- All tests use RefreshDatabase trait which automatically handles database
  transactions in SQLite (or truncation in other databases), ensuring clean
  test state with automatic rollback after each test
- Tests are organized following Laravel conventions
- Multi-tenancy tests ensure complete data isolation between companies
- Service tests validate business logic and data handling

