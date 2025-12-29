# AndoBill - Implementation Documentation

## 📋 Table of Contents

1. [System Overview](#system-overview)
2. [Architecture](#architecture)
3. [Modules](#modules)
4. [Database Structure](#database-structure)
5. [Backend Implementation](#backend-implementation)
6. [Frontend Implementation](#frontend-implementation)
7. [Security & Multi-Tenancy](#security--multi-tenancy)
8. [Features](#features)
9. [Services](#services)
10. [Testing](#testing)
11. [Deployment](#deployment)

---

## System Overview

### What is AndoBill?

AndoBill is a comprehensive, multi-tenant invoicing and billing system designed for German businesses. It provides complete invoice and offer management, customer relationship management, product catalog, payment tracking, and financial reporting.

### Key Characteristics

- **Multi-Tenant**: Supports multiple companies with complete data isolation
- **German-Focused**: Built for German tax and business requirements (UStG, DIN 5008, etc.)
- **Modern Stack**: Laravel 12 + React 19 + TypeScript
- **Role-Based**: Granular permissions system
- **E-Rechnung Ready**: Supports XRechnung and ZUGFeRD formats

---

## Architecture

### Technology Stack

#### Backend
- **Framework**: Laravel 12
- **Language**: PHP 8.2+
- **Database**: SQLite (default) / MySQL / MariaDB
- **PDF Generation**: DomPDF
- **E-Rechnung**: horstoeko/zugferd library
- **Permissions**: Spatie Laravel Permission
- **Queue**: Laravel Queue (database driver)

#### Frontend
- **Framework**: React 19
- **Language**: TypeScript
- **SPA Framework**: Inertia.js
- **Styling**: Tailwind CSS 4
- **Components**: Radix UI primitives
- **Icons**: Lucide React
- **Build Tool**: Vite
- **Routing**: Ziggy.js (Laravel routes in JS)

### Architecture Pattern

**Modular Monolith**: The application is organized into feature modules, each containing:
- Controllers
- Models
- Policies (Authorization)
- Routes (in `routes/modules/`)

**SPA with Server-Side Rendering**: Uses Inertia.js to create a single-page application experience while maintaining server-side rendering benefits.

---

## Modules

### 1. Company Module (`app/Modules/Company/`)

**Purpose**: Manage companies (tenants) in the multi-tenant system.

**Components**:
- `CompanyController` - CRUD operations for companies
- `Company` model - Company entity
- `CompanySetting` model - Company-specific settings

**Features**:
- Company creation and management
- Company settings (invoice prefixes, tax rates, etc.)
- Company switching (for super admins)
- Company status management (active/inactive)

**Routes**: `/companies/*`

**Key Methods**:
- `store()` - Create new company
- `update()` - Update company details
- `settings()` - Manage company settings
- `switch()` - Switch active company (super admin)

---

### 2. Invoice Module (`app/Modules/Invoice/`)

**Purpose**: Core invoice management - create, edit, send, and track invoices.

**Components**:
- `InvoiceController` - Invoice CRUD and operations
- `InvoiceLayoutController` - Invoice template management
- `Invoice` model - Invoice entity
- `InvoiceItem` model - Invoice line items
- `InvoiceLayout` model - Invoice templates
- `InvoicePolicy` - Authorization rules

**Features**:
- Create/edit invoices with line items
- Multiple invoice templates (clean, modern, professional, elegant, minimal, classic)
- PDF generation with customizable layouts
- Invoice status tracking (draft, sent, paid, overdue, cancelled)
- Invoice corrections (Stornorechnung) - German-compliant
- Reminder system (Mahnungen) - 5-level escalation
- Company snapshot - Preserves company data at invoice creation
- Payment tracking integration

**Routes**: `/invoices/*`

**Key Methods**:
- `store()` - Create invoice with items
- `update()` - Update invoice
- `pdf()` - Generate PDF
- `send()` - Send invoice via email
- `sendReminder()` - Send reminder (Mahnung)
- `correct()` - Create correction invoice (Stornorechnung)

**Database Tables**:
- `invoices` - Main invoice table
- `invoice_items` - Line items
- `invoice_layouts` - Template definitions

---

### 3. Offer Module (`app/Modules/Offer/`)

**Purpose**: Manage offers/quotes that can be converted to invoices.

**Components**:
- `OfferController` - Offer CRUD operations
- `OfferLayoutController` - Offer template management
- `Offer` model - Offer entity
- `OfferItem` model - Offer line items
- `OfferLayout` model - Offer templates
- `OfferPolicy` - Authorization rules

**Features**:
- Create/edit offers with line items
- Multiple offer templates
- PDF generation
- Status tracking (draft, sent, accepted, rejected, expired)
- Convert offer to invoice
- Expiry date management
- Company snapshot preservation

**Routes**: `/offers/*`

**Key Methods**:
- `store()` - Create offer
- `update()` - Update offer
- `pdf()` - Generate PDF
- `send()` - Send offer via email
- `convertToInvoice()` - Convert offer to invoice

**Database Tables**:
- `offers` - Main offer table
- `offer_items` - Line items
- `offer_layouts` - Template definitions

---

### 4. Customer Module (`app/Modules/Customer/`)

**Purpose**: Manage customer database and relationships.

**Components**:
- `CustomerController` - Customer CRUD
- `Customer` model - Customer entity
- `CustomerPolicy` - Authorization rules

**Features**:
- Customer CRUD operations
- Customer number generation
- Customer types (business/private)
- Contact information management
- Customer statistics (total revenue, invoice count)
- Customer status (active/inactive)

**Routes**: `/customers/*`

**Key Methods**:
- `store()` - Create customer with auto-generated number
- `update()` - Update customer
- `show()` - View customer with statistics

**Database Tables**:
- `customers` - Customer table

---

### 5. Product Module (`app/Modules/Product/`)

**Purpose**: Manage product catalog, categories, and inventory.

**Components**:
- `ProductController` - Product CRUD
- `CategoryController` - Category management
- `WarehouseController` - Warehouse management
- `Product` model - Product entity
- `Category` model - Product category
- `Warehouse` model - Warehouse entity
- `WarehouseStock` model - Stock levels
- `StockMovement` model - Stock transactions
- `ProductPolicy` - Authorization rules

**Features**:
- Product catalog with pricing
- Product categories
- SKU and barcode support
- Inventory tracking (optional)
- Warehouse management
- Stock movements tracking
- Product selection in invoices/offers
- Service vs. physical product distinction

**Routes**: `/products/*`, `/categories/*`, `/warehouses/*`

**Key Methods**:
- Product CRUD
- Category management
- Warehouse operations
- Stock adjustments

**Database Tables**:
- `products` - Product catalog
- `categories` - Product categories
- `warehouses` - Warehouse locations
- `warehouse_stocks` - Stock levels per warehouse
- `stock_movements` - Stock transaction history

---

### 6. Payment Module (`app/Modules/Payment/`)

**Purpose**: Track invoice payments (partial and full).

**Components**:
- `PaymentController` - Payment CRUD
- `Payment` model - Payment entity
- `PaymentPolicy` - Authorization rules

**Features**:
- Create payments for invoices
- Track partial payments
- Payment status (pending, completed, cancelled)
- Payment methods tracking
- Automatic invoice status update (paid when fully paid)
- Payment history per invoice

**Routes**: `/payments/*`

**Key Methods**:
- `store()` - Create payment, auto-update invoice status
- `update()` - Update payment
- `index()` - List payments with filters

**Database Tables**:
- `payments` - Payment records

**Integration**:
- Automatically updates invoice status when fully paid
- Used for income calculation (paid invoices = income)

---

### 7. Document Module (`app/Modules/Document/`)

**Purpose**: Manage document storage and linking.

**Components**:
- `DocumentController` - Document operations
- `Document` model - Document entity

**Features**:
- Upload documents
- Link documents to customers/invoices/offers
- Secure storage (private filesystem)
- Organized by `company_id/year/month/`
- Download with authentication
- Document deletion

**Routes**: `/documents/*`

**Key Methods**:
- `store()` - Upload document
- `download()` - Download document (authenticated)
- `destroy()` - Delete document

**Database Tables**:
- `documents` - Document metadata

**Storage**:
- Files stored in `storage/app/documents/{companyId}/{year}/{month}/`
- Not publicly accessible (requires authentication)

---

### 8. Reports Module (`app/Modules/Reports/`)

**Purpose**: Generate financial and business reports.

**Components**:
- `ReportsController` - Report generation

**Features**:
- Revenue reports (monthly, quarterly, yearly)
- Customer reports
- Tax reports
- Invoice statistics
- Offer statistics

**Routes**: `/reports/*`

**Key Methods**:
- `revenue()` - Revenue analysis
- `customer()` - Customer reports
- `tax()` - Tax reports

---

### 9. Dashboard Module (`app/Modules/Dashboard/`)

**Purpose**: Main dashboard with statistics and overview.

**Components**:
- `DashboardController` - Dashboard data

**Features**:
- Real-time statistics
- Recent invoices/offers
- Alerts (overdue invoices, expiring offers)
- Revenue trends
- Customer statistics

**Routes**: `/dashboard`

**Key Methods**:
- `index()` - Dashboard data aggregation

---

### 10. Settings Module (`app/Modules/Settings/`)

**Purpose**: Manage company and user settings.

**Components**:
- `SettingsController` - Settings management
- `GlobalSetting` model - Global settings

**Features**:
- Company settings (invoice prefixes, tax rates, etc.)
- Email settings (SMTP configuration)
- E-Rechnung settings
- Reminder (Mahnung) settings
- Email logs viewing
- Document management settings

**Routes**: `/settings/*`

**Key Methods**:
- `company()` - Company settings
- `email()` - Email configuration
- `erechnung()` - E-Rechnung settings
- `emailLogs()` - View email logs

---

### 11. User Module (`app/Modules/User/`)

**Purpose**: User and permission management.

**Components**:
- `UserController` - User CRUD
- `RoleController` - Role management
- `PermissionController` - Permission management
- `CompanyContextController` - Company switching
- `User` model - User entity

**Features**:
- User CRUD operations
- Role assignment
- Permission management
- Company assignment
- Company switching (super admin)

**Routes**: `/users/*`, `/roles/*`, `/permissions/*`

---

### 12. Calendar Module (`app/Modules/Calendar/`)

**Purpose**: Calendar view for invoices and offers.

**Components**:
- `CalendarController` - Calendar data

**Features**:
- Invoice due dates
- Overdue invoices
- Offer expiry dates
- Calendar view

**Routes**: `/calendar`

---

### 13. Help Module (`app/Modules/Help/`)

**Purpose**: Help and documentation.

**Components**:
- `HelpController` - Help pages

**Routes**: `/help`

---

## Database Structure

### Core Tables

#### Companies & Users
- `companies` - Company/tenant records
- `users` - User accounts
- `company_settings` - Company-specific settings
- `global_settings` - System-wide settings

#### Invoicing
- `invoices` - Invoice records
- `invoice_items` - Invoice line items
- `invoice_layouts` - Invoice templates
- `offers` - Offer records
- `offer_items` - Offer line items
- `offer_layouts` - Offer templates

#### Customers & Products
- `customers` - Customer database
- `products` - Product catalog
- `categories` - Product categories
- `warehouses` - Warehouse locations
- `warehouse_stocks` - Stock levels
- `stock_movements` - Stock transactions

#### Payments & Documents
- `payments` - Payment records
- `documents` - Document metadata

#### System
- `email_logs` - Email sending history
- `roles` - User roles (Spatie)
- `permissions` - Permissions (Spatie)
- `model_has_roles` - Role assignments (Spatie)
- `model_has_permissions` - Permission assignments (Spatie)

### Key Relationships

```
Company
├── Users (many)
├── Customers (many)
├── Invoices (many)
├── Offers (many)
├── Products (many)
└── Settings (one)

Invoice
├── Company (belongs to)
├── Customer (belongs to)
├── User (created by)
├── Items (has many)
├── Payments (has many)
├── Layout (belongs to)
└── CorrectsInvoice (belongs to - for corrections)

Payment
├── Invoice (belongs to)
├── Company (belongs to)
└── CreatedBy (user)

Customer
├── Company (belongs to)
├── Invoices (has many)
└── Offers (has many)
```

---

## Backend Implementation

### Controllers

All controllers extend `App\Http\Controllers\Controller` which provides:
- `getEffectiveCompanyId()` - Get current company context
- `authorize()` - Policy-based authorization
- Multi-tenancy helpers

### Models

All models use:
- `HasUuids` trait - UUID primary keys
- `HasFactory` trait - Model factories
- `forCompany()` scope - Multi-tenancy filtering

### Policies

Authorization is handled via Laravel Policies:
- `InvoicePolicy`
- `OfferPolicy`
- `CustomerPolicy`
- `ProductPolicy`
- `PaymentPolicy`
- etc.

### Services

#### ContextService (`app/Services/ContextService.php`)
- User context management
- Company context management
- Dashboard statistics
- Caching

#### SettingsService (`app/Services/SettingsService.php`)
- Company settings management
- Global settings management
- Setting value casting
- Cache management

#### ERechnungService (`app/Services/ERechnungService.php`)
- XRechnung generation
- ZUGFeRD generation
- E-Rechnung compliance

### Traits

#### LogsEmails (`app/Traits/LogsEmails.php`)
- Reusable email logging
- Used by Invoice and Offer controllers
- Logs to `email_logs` table

### Middleware

- `HandleInertiaRequests` - Shares data with Inertia.js
- Authentication middleware
- Company context middleware

---

## Frontend Implementation

### Page Structure

```
resources/js/pages/
├── dashboard.tsx
├── invoices/
│   ├── index.tsx
│   ├── create.tsx
│   ├── edit.tsx
│   └── show.tsx
├── offers/
│   ├── index.tsx
│   ├── create.tsx
│   ├── edit.tsx
│   └── show.tsx
├── customers/
│   ├── index.tsx
│   ├── create.tsx
│   ├── edit.tsx
│   └── show.tsx
├── products/
│   ├── index.tsx
│   ├── create.tsx
│   ├── edit.tsx
│   └── show.tsx
├── payments/
│   ├── index.tsx
│   ├── create.tsx
│   ├── edit.tsx
│   └── show.tsx
├── settings/
│   ├── company.tsx
│   ├── email.tsx
│   ├── erechnung.tsx
│   └── email-logs.tsx
└── reports/
    ├── revenue.tsx
    ├── customer.tsx
    └── tax.tsx
```

### Components

```
resources/js/components/
├── ui/              # Radix UI components (button, card, etc.)
├── app-sidebar.tsx  # Main navigation
├── product-selector-dialog.tsx
└── ... (many more)
```

### Layouts

```
resources/js/layouts/
├── app-layout.tsx      # Main application layout
├── auth-layout.tsx     # Authentication layout
└── settings-layout.tsx # Settings pages layout
```

### Routing

- Uses Inertia.js for SPA navigation
- Routes defined in Laravel (`routes/web.php`, `routes/modules/*`)
- Ziggy.js provides route helpers in JavaScript
- Routes loaded dynamically via API (hidden from HTML source)

---

## Security & Multi-Tenancy

### Multi-Tenancy Implementation

**Company Isolation**:
- All models have `company_id` column
- `forCompany()` scope on all models
- Policies enforce company ownership
- Super admin can access all companies

**Data Isolation**:
- Users can only see their company's data
- Queries automatically filtered by `company_id`
- Cross-company access prevented by policies

### Authentication & Authorization

**Authentication**:
- Laravel's built-in authentication
- Session-based
- Password hashing (bcrypt)

**Authorization**:
- Spatie Laravel Permission package
- Role-based (super_admin, admin, user)
- Permission-based (granular permissions)
- Policy-based (model-level authorization)

**Roles**:
- **Super Admin**: Full system access, all companies
- **Admin**: Full access within company
- **User**: Limited access (create invoices, view reports)

### Security Features

- CSRF protection
- SQL injection protection (Eloquent ORM)
- XSS protection (Blade templating)
- Route protection (auth middleware)
- File upload validation
- Secure document storage (private filesystem)
- Routes hidden from HTML source

---

## Features

### 1. Invoice Management

**Core Features**:
- Create/edit invoices with line items
- Multiple invoice templates (6 templates)
- PDF generation with DomPDF
- Email sending with attachments
- Status tracking
- Payment tracking
- Reminder system (Mahnungen)

**Advanced Features**:
- Invoice corrections (Stornorechnung)
- Company snapshot (preserves historical data)
- Custom layouts per invoice
- DIN 5008 compliant address blocks
- Tax calculations
- Multi-currency ready (currently EUR)

### 2. Offer Management

**Core Features**:
- Create/edit offers with line items
- Multiple offer templates
- PDF generation
- Email sending
- Status tracking
- Convert to invoice
- Expiry date management

### 3. Payment Tracking

**Core Features**:
- Track partial payments
- Track full payments
- Payment methods
- Payment history
- Automatic invoice status updates

### 4. Reminder System (Mahnungen)

**5-Level Escalation**:
1. Friendly Reminder (Freundliche Erinnerung) - 7 days
2. 1st Mahnung - 14 days, €5 fee
3. 2nd Mahnung - 21 days, €10 fee
4. 3rd Mahnung - 30 days, €15 fee
5. Inkasso - 45 days

**Features**:
- Automatic escalation
- Configurable intervals
- Fee calculation
- Email templates per level
- Reminder history tracking

### 5. E-Rechnung (Electronic Invoicing)

**Supported Formats**:
- XRechnung (XML)
- ZUGFeRD (PDF + XML)

**Features**:
- EU EN 16931 compliant
- Automatic generation from invoices
- Download endpoints
- Configurable profiles

### 6. Email System

**Features**:
- SMTP configuration per company
- Email templates
- Email logging
- Attachment support
- Email history

### 7. Document Management

**Features**:
- Secure document storage
- Link to customers/invoices/offers
- Organized by company/year/month
- Authenticated downloads
- Document deletion

### 8. Reports

**Available Reports**:
- Revenue reports (monthly, quarterly, yearly)
- Customer reports
- Tax reports
- Invoice statistics
- Offer statistics

---

## Services

### ContextService

**Purpose**: Manage user and company context, provide dashboard statistics.

**Key Methods**:
- `getUserContext()` - Get current user with company
- `getCompanyContext()` - Get current company
- `getDashboardStats()` - Calculate dashboard statistics
- `getEffectiveCompanyId()` - Get active company ID

**Caching**: Uses Laravel cache for performance

### SettingsService

**Purpose**: Manage company and global settings.

**Key Methods**:
- `getSetting($key, $default)` - Get company setting
- `setSetting($key, $value)` - Set company setting
- `getAll()` - Get all company settings
- `getGlobalSetting($key)` - Get global setting

**Storage**: Settings stored in `company_settings` and `global_settings` tables

### ERechnungService

**Purpose**: Generate E-Rechnung compliant invoices.

**Key Methods**:
- `generateXRechnung($invoice)` - Generate XRechnung XML
- `generateZugferd($invoice)` - Generate ZUGFeRD PDF
- `downloadXRechnung($invoice)` - Download XRechnung
- `downloadZugferd($invoice)` - Download ZUGFeRD

**Library**: horstoeko/zugferd

---

## Testing

### Test Structure

**Unit Tests** (`tests/Unit/`):
- Service tests (ContextService, SettingsService, ERechnungService)

**Feature Tests** (`tests/Feature/`):
- Module tests (Company, Dashboard, Document, Calendar, Reports)
- Multi-tenancy tests (39 comprehensive tests)
- Authentication tests
- Settings tests

### Test Coverage

**Covered**:
- Multi-tenancy isolation
- Policy enforcement
- CRUD operations
- Service logic
- Data integrity

**Test Execution**:
```bash
php artisan test
```

---

## Deployment

### Requirements

- PHP 8.2+
- Composer
- Node.js 18+
- SQLite or MySQL/MariaDB
- Web server (Apache/Nginx)

### Deployment Steps

1. **Clone Repository**
2. **Install Dependencies**
   ```bash
   composer install --optimize-autoloader --no-dev
   npm install
   npm run build
   ```
3. **Environment Setup**
   - Copy `.env.example` to `.env`
   - Configure database, mail, etc.
4. **Database Setup**
   ```bash
   php artisan migrate --force
   php artisan db:seed
   ```
5. **Storage Setup**
   ```bash
   php artisan storage:link
   ```
6. **Optimize**
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

See [DEPLOYMENT.md](DEPLOYMENT.md) for detailed instructions.

---

## Key Implementation Details

### Company Snapshot

**Purpose**: Preserve company data at invoice/offer creation time.

**Implementation**:
- Stored as JSON in `company_snapshot` column
- Created automatically when invoice/offer is created
- Used in PDF generation to show historical data
- Prevents changes to company info from affecting old documents

### Invoice Corrections (Stornorechnung)

**Purpose**: German-compliant invoice cancellation.

**Implementation**:
- Creates new invoice with negative amounts
- Links to original invoice
- Marks original as "cancelled"
- Both invoices kept for audit trail

### Payment Integration

**Purpose**: Track invoice payments and calculate income.

**Implementation**:
- Payments linked to invoices
- Automatic invoice status update
- Used for income calculation (paid invoices = income)
- Supports partial payments

### Document Security

**Purpose**: Secure document storage.

**Implementation**:
- Documents stored in private filesystem (`storage/app/documents/`)
- Organized by `company_id/year/month/`
- Downloads require authentication
- Files not publicly accessible

### Route Privacy

**Purpose**: Hide routes from HTML source.

**Implementation**:
- Routes loaded dynamically via `/api/routes` endpoint
- Ziggy.js initialized from API response
- Routes not in HTML source code
- Still available in JavaScript

---

## File Organization

### Backend Structure

```
app/
├── Console/Commands/        # Artisan commands
├── Http/
│   ├── Controllers/         # Base controllers
│   ├── Middleware/          # Custom middleware
│   └── Requests/            # Form requests
├── Modules/                 # Feature modules
│   ├── Company/
│   ├── Customer/
│   ├── Invoice/
│   ├── Offer/
│   ├── Payment/
│   ├── Product/
│   └── ...
├── Models/                  # Global models (EmailLog)
├── Services/                # Business logic services
└── Traits/                  # Reusable traits
```

### Frontend Structure

```
resources/js/
├── app.tsx                  # Application entry point
├── components/              # Reusable components
│   ├── ui/                  # UI primitives
│   └── ...                  # Feature components
├── pages/                   # Page components
│   ├── invoices/
│   ├── offers/
│   ├── customers/
│   └── ...
├── layouts/                 # Layout components
├── hooks/                   # React hooks
├── plugins/                 # Plugins (Ziggy)
└── types/                   # TypeScript types
```

### Routes Structure

```
routes/
├── web.php                  # Main routes
├── auth.php                 # Authentication routes
├── settings.php             # User settings routes
└── modules/                 # Module routes
    ├── invoices.php
    ├── offers.php
    ├── customers.php
    └── ...
```

---

## Configuration

### Key Configuration Files

- `config/app.php` - Application configuration
- `config/database.php` - Database configuration
- `config/filesystems.php` - Storage configuration
- `config/permission.php` - Permission package config
- `config/dompdf.php` - PDF generation config
- `config/inertia.php` - Inertia.js configuration

### Environment Variables

Key `.env` variables:
- `APP_NAME` - Application name
- `APP_URL` - Application URL
- `DB_CONNECTION` - Database type
- `MAIL_*` - Email configuration
- `FILESYSTEM_DISK` - Storage disk

---

## API Endpoints

### Authentication
- `POST /login` - User login
- `POST /logout` - User logout
- `GET /api/routes` - Get Ziggy routes (authenticated)

### Main Resources
- `GET /invoices` - List invoices
- `POST /invoices` - Create invoice
- `GET /invoices/{id}` - Show invoice
- `PUT /invoices/{id}` - Update invoice
- `DELETE /invoices/{id}` - Delete invoice
- `GET /invoices/{id}/pdf` - Generate PDF
- `POST /invoices/{id}/send` - Send invoice

(Same pattern for offers, customers, products, payments, etc.)

---

## Data Flow

### Invoice Creation Flow

1. User fills form in React component
2. Form submitted via Inertia.js
3. `InvoiceController@store()` receives request
4. Validates data
5. Creates invoice with items
6. Creates company snapshot
7. Calculates totals
8. Saves to database
9. Returns to invoice list
10. React component updates

### PDF Generation Flow

1. User clicks "Generate PDF"
2. `InvoiceController@pdf()` called
3. Loads invoice with relationships
4. Gets layout (invoice-specific or default)
5. Renders Blade template
6. DomPDF converts to PDF
7. Returns PDF response
8. Browser downloads PDF

### Payment Flow

1. User creates payment for invoice
2. `PaymentController@store()` creates payment
3. Calculates total paid amount
4. If fully paid, updates invoice status to "paid"
5. Payment saved to database
6. Invoice view shows updated payment status

---

## Performance Considerations

### Caching

- Dashboard statistics cached
- Company settings cached
- User context cached
- Cache cleared on updates

### Database Optimization

- Indexes on foreign keys
- Indexes on frequently queried columns
- Eager loading relationships
- Query optimization

### Frontend Optimization

- Code splitting (Vite)
- Asset optimization
- Lazy loading components
- Efficient state management

---

## Localization

### Current Language

- **German (de)** - Primary language
- Interface text in German
- Date formats (DD.MM.YYYY)
- Currency (EUR, €)
- Number formats (German style)

### Translation System

- Text hardcoded in components (can be moved to translation files)
- Ready for i18n implementation
- Date formatting via Carbon
- Currency formatting via helpers

---

## Known Limitations

1. **Single Currency**: Currently EUR only (multi-currency ready)
2. **Single Language**: German only (i18n ready)
3. **No Recurring Invoices**: Not yet implemented
4. **No Expenses Module**: Planned (see EXPENSES_IMPLEMENTATION_PLAN.md)
5. **No Bank Integration**: Manual payment entry only

---

## Future Enhancements

### Planned Features

1. **Expenses Module** - Track business expenses
2. **Recurring Invoices** - Automatic invoice generation
3. **Bank Integration** - Import bank statements
4. **Multi-Currency** - Support multiple currencies
5. **API** - RESTful API for integrations
6. **Mobile App** - React Native app

---

## Maintenance

### Regular Tasks

- Clear cache: `php artisan cache:clear`
- Clear views: `php artisan view:clear`
- Optimize: `php artisan optimize`
- Backup database
- Monitor logs

### Updates

- `composer update` - Update PHP dependencies
- `npm update` - Update JavaScript dependencies
- Run migrations: `php artisan migrate`
- Clear caches after updates

---

## Support & Resources

### Documentation Files

- [README.md](README.md) - Getting started
- [COMPANY_WIZARD.md](COMPANY_WIZARD.md) - Setup wizard
- [E-RECHNUNG_IMPLEMENTATION.md](E-RECHNUNG_IMPLEMENTATION.md) - E-Rechnung guide
- [MAHNUNG_SYSTEM.md](MAHNUNG_SYSTEM.md) - Reminder system
- [ROLES_AND_PERMISSIONS.md](ROLES_AND_PERMISSIONS.md) - Permissions
- [RECHNUNGSKORREKTUR_IMPLEMENTATION.md](RECHNUNGSKORREKTUR_IMPLEMENTATION.md) - Invoice corrections
- [DOCUMENT_SECURITY.md](DOCUMENT_SECURITY.md) - Document security
- [EMAIL_LOGS_SYSTEM.md](EMAIL_LOGS_SYSTEM.md) - Email logging

### Testing Documentation

- [tests/TEST_COVERAGE_SUMMARY.md](tests/TEST_COVERAGE_SUMMARY.md)
- [tests/TEST_STATUS.md](tests/TEST_STATUS.md)

---

## Version Information

**Current Version**: 1.0.0  
**Last Updated**: December 2024  
**Laravel Version**: 12.x  
**React Version**: 19.x  
**PHP Version**: 8.2+

---

**This documentation is maintained alongside the codebase and updated as features are added or changed.**

