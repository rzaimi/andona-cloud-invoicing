# Roles and Permissions Overview

This document describes the three roles in the invoicing system and their capabilities.

## Role Hierarchy

1. **Super Admin** - Full system access across all companies
2. **Admin** - Full access within their assigned company
3. **User** - Basic access to core invoicing features within their company

---

## 🔴 Super Admin (`super_admin`)

### Permissions
- ✅ **All permissions** (inherits everything)
  - `manage_users` - User management
  - `manage_companies` - Company management
  - `manage_settings` - System settings
  - `manage_invoices` - Invoice management
  - `manage_offers` - Offer management
  - `manage_products` - Product management
  - `view_reports` - View reports

### Capabilities

#### Company Management
- **View all companies** across the system
- **Create, edit, and delete companies**
- **Switch company context** - Can select any company from the dropdown to view/manage their data
- **Manage company settings** for any company
- **View all users** from all companies
- **Assign users to any company**

#### User Management
- **Create, edit, and delete users** in any company
- **Assign users to any company**
- **Manage roles and permissions** for all users
- **Create custom roles** with specific permissions
- **Manage permissions** independently

#### Data Access
- **View all invoices** from all companies (when company is selected)
- **View all offers** from all companies (when company is selected)
- **View all products** from all companies (when company is selected)
- **View all customers** from all companies (when company is selected)
- **Access dashboard stats** for any selected company
- **Access all reports** across companies

#### Special Features
- **Company switcher** in sidebar - Dropdown to select which company's data to view/manage
- **Multi-tenant context switching** - Seamlessly switch between companies
- **No restrictions** on data access within selected company context

---

## 🟡 Admin (`admin`)

### Permissions
- ✅ `manage_users` - User management
- ✅ `manage_settings` - Settings management
- ✅ `manage_invoices` - Invoice management
- ✅ `manage_offers` - Offer management
- ✅ `manage_products` - Product management
- ✅ `view_reports` - View reports
- ❌ `manage_companies` - **Cannot manage companies**

### Capabilities

#### User Management (Within Own Company)
- **View all users** in their assigned company
- **Create, edit, and delete users** within their company
- **Cannot assign users to other companies**
- **Cannot manage admin users** (except themselves if they're an admin)
- **Limited to managing users in their own company only**

#### Company & Settings
- **Cannot create, edit, or delete companies**
- **Cannot switch company context** (locked to their assigned company)
- **Can manage settings** for their own company
- **View company information** (read-only, cannot modify company details)

#### Data Access (Within Own Company)
- **Full access to all invoices** in their company (create, edit, delete, view)
- **Full access to all offers** in their company (create, edit, delete, view)
- **Full access to all products** in their company (create, edit, delete, view)
- **Full access to all customers** in their company (create, edit, delete, view)
- **Access dashboard** with stats for their company
- **View reports** for their company
- **Manage categories** for products
- **Manage warehouses** and stock levels
- **Manage invoice/offer layouts** and templates

#### Restrictions
- **Cannot access data from other companies**
- **Cannot delete products** unless they have `manage_users` permission (which they do)
- **Company-scoped** - All actions limited to `company_id` matching their assigned company

---

## 🟢 User (`user`)

### Permissions
- ✅ `manage_invoices` - Invoice management
- ✅ `manage_offers` - Offer management
- ✅ `manage_products` - Product management
- ✅ `view_reports` - View reports
- ❌ `manage_users` - **Cannot manage users**
- ❌ `manage_companies` - **Cannot manage companies**
- ❌ `manage_settings` - **Cannot manage settings**

### Capabilities

#### Invoice Management (Within Own Company)
- **Create invoices** for their company
- **Edit invoices** they have access to
- **View invoices** in their company
- **Generate PDF** invoices
- **Send invoices** to customers
- **Mark invoices** as paid/unpaid
- **Delete invoices** (if company policy allows)

#### Offer Management (Within Own Company)
- **Create offers** for their company
- **Edit offers** they have access to
- **View offers** in their company
- **Convert offers to invoices**
- **Generate PDF** offers
- **Delete offers** (if company policy allows)

#### Product Management (Within Own Company)
- **Create products** for their company
- **Edit products** in their company
- **View products** in their company
- **Cannot delete products** (requires `manage_users` permission or admin role)
- **Manage product categories** (create, edit categories)
- **View warehouse stock** levels
- **Adjust stock** quantities (if enabled)

#### Customer Management (Within Own Company)
- **Create customers** for their company
- **Edit customers** in their company
- **View customers** in their company
- **Delete customers** (if policy allows)

#### Reports & Dashboard
- **View dashboard** with company statistics
- **View reports** for their company
- **Export data** (if enabled)
- **View calendar** with invoice due dates and offer expiry dates

#### Restrictions
- **Cannot manage users** - Cannot create, edit, or delete other users
- **Cannot manage companies** - Cannot create or modify companies
- **Cannot manage system settings** - Cannot change company settings
- **Cannot delete products** - Limited deletion rights
- **Company-scoped** - All data access limited to their assigned company
- **No cross-company access** - Cannot view data from other companies

---

## Permission Details

### `manage_users`
- Create, edit, and delete users
- Assign roles to users
- Manage user permissions
- View user lists
- **Required for:** User management UI access

### `manage_companies`
- Create, edit, and delete companies
- Switch company context (super admin feature)
- View all companies
- Assign users to companies
- **Required for:** Company management UI access

### `manage_settings`
- Modify company settings (currency, tax rates, prefixes, etc.)
- Manage invoice/offer layouts
- Configure system preferences
- **Required for:** Settings page access

### `manage_invoices`
- Create, edit, delete invoices
- Generate PDF invoices
- Mark invoices as paid
- View invoice reports
- **Required for:** Invoice management UI access

### `manage_offers`
- Create, edit, delete offers
- Generate PDF offers
- Convert offers to invoices
- View offer reports
- **Required for:** Offer management UI access

### `manage_products`
- Create, edit products
- Manage product categories
- Manage warehouses and stock
- Adjust inventory levels
- **Required for:** Product management UI access
- **Note:** Product deletion requires additional `manage_users` permission or admin role

### `view_reports`
- Access dashboard statistics
- View financial reports
- Export data reports
- View calendar events
- **Required for:** Reports and dashboard access

---

## Authorization Rules

### Multi-Tenancy
- All users are **scoped to their assigned company** (`company_id`)
- **Super admins** can override this by selecting a company from the switcher
- Regular users and admins can **only access data** where `company_id` matches their assigned company

### Policy Enforcement
- **Customer, Invoice, Offer, Product policies** check:
  1. User's `company_id` matches the resource's `company_id`, OR
  2. User has `manage_companies` permission (super admin)

### Data Filtering
- Controllers use `getEffectiveCompanyId()` which:
  - Returns selected company ID from session (for super admins)
  - Returns user's assigned company ID (for regular users)
- All queries are filtered by this effective company ID

---

## Examples

### Example 1: Super Admin Workflow
1. Super admin logs in
2. Sees company switcher in sidebar
3. Selects "Company A" from dropdown
4. Views invoices, products, customers for Company A
5. Switches to "Company B"
6. Now sees all data for Company B
7. Can create/edit/delete anything in either company

### Example 2: Admin Workflow
1. Admin logs in (assigned to "Company A")
2. No company switcher visible
3. Views invoices, products, customers for Company A only
4. Can create new users in Company A
5. Cannot see or access Company B data
6. Can manage all settings for Company A

### Example 3: User Workflow
1. User logs in (assigned to "Company A")
2. Views invoices and offers for Company A
3. Creates new products for Company A
4. Cannot delete products (needs admin/manage_users)
5. Cannot manage other users
6. Cannot access settings or company management

---

## Summary Table

| Capability | Super Admin | Admin | User |
|------------|-------------|-------|------|
| Manage Companies | ✅ All | ❌ None | ❌ None |
| Switch Company Context | ✅ Yes | ❌ No | ❌ No |
| Manage Users | ✅ All Companies | ✅ Own Company | ❌ None |
| Manage Settings | ✅ All Companies | ✅ Own Company | ❌ None |
| Manage Invoices | ✅ All Companies* | ✅ Own Company | ✅ Own Company |
| Manage Offers | ✅ All Companies* | ✅ Own Company | ✅ Own Company |
| Manage Products | ✅ All Companies* | ✅ Own Company | ✅ Own Company |
| Delete Products | ✅ Yes | ✅ Yes | ❌ No |
| View Reports | ✅ All Companies* | ✅ Own Company | ✅ Own Company |
| Manage Roles/Permissions | ✅ Yes | ❌ No | ❌ No |

*When a company is selected via company switcher

---

## Notes

- **Role vs Permission**: The system uses Spatie Laravel Permission package
- **Permission-based**: UI elements check for permissions, not just roles
- **Flexible**: Custom permissions can be created and assigned to roles
- **Secure**: All actions are validated through policies
- **Multi-tenant**: Strong data isolation between companies

