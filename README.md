# AndoBill - Multi-Tenant Invoicing System

A comprehensive, modern invoicing and billing system built with Laravel 13 and React 19. Designed for multi-tenant use with role-based access control, supporting invoices, offers, customers, products, and more.

## 🌟 Features

### Core Functionality
- **📄 Invoice Management** - Create, edit, and manage invoices with PDF generation
- **📋 Offer Management** - Create offers/quotes that can be converted to invoices
- **👥 Customer Management** - Comprehensive customer database with contact information
- **📦 Product Management** - Product catalog with categories, pricing, and inventory tracking
- **🏢 Multi-Tenant Architecture** - Support for multiple companies with data isolation
- **📊 Dashboard & Reports** - Real-time statistics and comprehensive reporting

### Advanced Features
- **🔐 Role-Based Access Control** - Super Admin, Admin, and User roles with granular permissions
- **🌍 Multi-Language Support** - German interface with extensible translation system
- **📧 Email Integration** - SMTP configuration for sending invoices and offers
- **🔄 Reminder System** - Automated German-compliant dunning process (Mahnverfahren)
- **📄 E-Rechnung Support** - EU EN 16931 compliant electronic invoicing (XRechnung, ZUGFeRD)
- **🎨 Customizable Layouts** - Multiple invoice and offer templates
- **💾 Invoice Corrections** - Support for invoice corrections and cancellations
- **📈 Warehouse Management** - Stock tracking and warehouse operations
- **🌙 Dark Mode** - System, light, and dark themes

### User Experience
- **⚡ Modern UI** - Built with React, TypeScript, and Tailwind CSS
- **📱 Responsive Design** - Works seamlessly on desktop, tablet, and mobile
- **🚀 Inertia.js** - Single-page application experience without API complexity
- **🎯 Intuitive Navigation** - Clean sidebar navigation with breadcrumbs

## 🛠 Technology Stack

### Backend
- **Laravel 13** - PHP framework
- **PHP 8.3+** - Programming language
- **SQLite/MySQL** - Database
- **Spatie Laravel Permission** - Role and permission management
- **DomPDF** - PDF generation
- **ZUGFeRD Library** - E-Rechnung support

### Frontend
- **React 19** - UI library
- **TypeScript** - Type safety
- **Inertia.js** - SPA framework
- **Tailwind CSS 4** - Styling
- **Radix UI** - Accessible component primitives
- **Vite** - Build tool

## 📋 Requirements

- PHP 8.3 or higher
- Composer
- Node.js 22+ and npm
- SQLite (default) or MySQL/MariaDB
- Web server (Apache/Nginx) or PHP built-in server

## 🚀 Installation

### 1. Clone the Repository
```bash
git clone https://github.com/rzaimi/andona-cloud-invoicing.git
cd invoicing
```

### 2. Install PHP Dependencies
```bash
composer install
```

### 3. Install JavaScript Dependencies
```bash
npm install
```

### 4. Environment Configuration
```bash
cp .env.example .env
php artisan key:generate
```

Update `.env` with your database and application settings:
```env
APP_NAME="AndoBill"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=sqlite
# Or use MySQL:
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=invoicing
# DB_USERNAME=root
# DB_PASSWORD=
```

### 5. Database Setup
```bash
# For SQLite (default)
touch database/database.sqlite

# Run migrations
php artisan migrate

# Seed database with sample data
php artisan db:seed
```

### 6. Build Frontend Assets
```bash
# Development
npm run dev

# Production
npm run build
```

### 7. Start Development Server
```bash
php artisan serve
```

Visit `http://localhost:8000` in your browser.

## 🔧 Configuration

### Initial Setup Wizard

The application includes a 7-step company setup wizard:

1. **Company Information** - Basic company details
2. **Email Configuration** - SMTP settings for sending invoices
3. **Invoice & Offer Settings** - Prefixes, currency, tax rates
4. **Mahnung Settings** - German dunning process configuration
5. **Banking Information** - Payment details
6. **Layout Selection** - Choose default templates
7. **Review & Complete** - Final review and activation

### Default Credentials

After seeding:
- **Super Admin**: `superadmin@example.com` / `password`
- **Admin** (per company): `admin@[company].com` / `password`
- **User** (per company): `john@[company].com` / `password`

⚠️ **Important**: Change default passwords in production!

## 📚 Usage

### Creating an Invoice

1. Navigate to **Rechnungen** → **Neue Rechnung**
2. Select a customer
3. Add items (from products or custom items)
4. Review totals
5. Save as draft or send directly

### Managing Customers

1. Go to **Kunden** → **Neuer Kunde**
2. Enter customer details
3. Save and start creating invoices/offers

### Product Catalog

1. Navigate to **Produkte** → **Neues Produkt**
2. Add product details, pricing, and category
3. Enable stock tracking if needed
4. Products can be selected when creating invoices

### E-Rechnung Setup

1. Go to **Einstellungen** → **E-Rechnung**
2. Enable E-Rechnung features
3. Configure format (XRechnung, ZUGFeRD)
4. Set electronic address
5. Generate compliant invoices

### Reminder System (Mahnungen)

The system supports automated German dunning process:

1. Configure intervals in **Einstellungen** → **Erinnerungen**
2. Set fees for each escalation level
3. Enable auto-send for automatic reminders
4. System will track and send reminders based on due dates

## 👥 Roles & Permissions

### Super Admin
- Full access to all companies
- User and company management
- System-wide settings
- Cross-company data access

### Admin
- Full access within assigned company
- Manage invoices, offers, customers, products
- Company settings (limited)
- Cannot manage users or companies

### User
- Create and manage invoices/offers
- View customers and products
- Access reports
- Cannot manage settings or users

See [COMPLETE_DOCUMENTATION.md](COMPLETE_DOCUMENTATION.md) for detailed permissions and role information.

## 📁 Project Structure

```
invoicing/
├── app/
│   ├── Http/Controllers/        # Base controllers
│   ├── Modules/                  # Feature modules
│   │   ├── Company/
│   │   ├── Customer/
│   │   ├── Invoice/
│   │   ├── Offer/
│   │   ├── Product/
│   │   ├── Settings/
│   │   └── User/
│   └── Services/                 # Business logic services
├── database/
│   ├── migrations/              # Database migrations
│   └── seeders/                 # Database seeders
├── resources/
│   ├── js/                      # React/TypeScript frontend
│   │   ├── components/          # Reusable components
│   │   ├── layouts/             # Layout components
│   │   └── pages/               # Page components
│   ├── views/                   # Blade templates
│   └── css/                     # Stylesheets
└── routes/
    ├── web.php                  # Main routes
    ├── settings.php             # Settings routes
    └── modules/                 # Module-specific routes
```

## 🧪 Testing

```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Feature
```

## 📝 Development

### Code Style
```bash
# Format PHP code
./vendor/bin/pint

# Format JavaScript/TypeScript
npm run format

# Lint JavaScript/TypeScript
npm run lint
```

### Development Mode
```bash
# Start all development services
composer dev

# This runs:
# - Laravel server
# - Queue worker
# - Log viewer (Pail)
# - Vite dev server
```

## 📄 Documentation

For complete documentation including implementation details, security audits, feature documentation, and more, see [COMPLETE_DOCUMENTATION.md](COMPLETE_DOCUMENTATION.md).

This comprehensive documentation includes:
- System architecture and implementation details
- Security audits and Phase 1 security implementation
- E-Rechnung implementation
- Reminder system (Mahnungen)
- Roles & permissions
- Invoice corrections
- Expense management
- Document security
- Deployment guides
- Testing documentation
- And much more...

## 🔒 Security

- CSRF protection enabled
- SQL injection protection via Eloquent ORM
- XSS protection via Blade templating
- Authentication required for all routes
- Role-based access control
- Company data isolation

## 🚀 Deployment

For detailed deployment instructions, especially for hosting environments with Node.js version constraints, see [COMPLETE_DOCUMENTATION.md](COMPLETE_DOCUMENTATION.md).

After the Laravel 13 upgrade:
- Production PHP must be **8.3+** (CI uses 8.4).
- Pin `SESSION_COOKIE`, `CACHE_PREFIX`, and `REDIS_PREFIX` in `.env` so Laravel 13's hyphenated defaults do not drop existing sessions or cache keys. See `.env.example`.
- Deploy frontend assets with `npm run build:ssr` (client + SSR bundle).

## 🆘 Support

For issues or questions, please contact the development team.

## 🙏 Acknowledgments

- Built with [Laravel](https://laravel.com)
- UI components from [Radix UI](https://www.radix-ui.com)
- Icons from [Lucide](https://lucide.dev)
- E-Rechnung support via [ZUGFeRD](https://www.ferd-net.de)

---

## 📄 License

This is proprietary software. All rights reserved.

---

## 🆕 Recent Updates (February 2026)

### GoBD Compliance & Invoice Editing Standards
- **German Accounting Standards (GoBD)**: Implemented full compliance for invoice editing
- **Status-Based Editing**: Only draft invoices can be edited; sent/paid invoices are locked
- **Audit Logging**: Complete audit trail for all invoice state changes
- **Stornorechnung Permission**: Role-based access control for invoice corrections
- **Automatic Status Updates**: Invoices automatically transition from "sent" to "paid" when payment is recorded

### Item-Level Tax Rates (USt.)
- **Per-Item Tax Configuration**: Each invoice/offer item can have its own tax rate
- **German Standard Rates**: Support for 19% (Regelsteuersatz), 7% (Ermäßigter Satz), 0% (Steuerfrei)
- **Mixed Rate Calculations**: Correctly calculate totals with different tax rates per item
- **Product Presets**: Products can have pre-configured tax rates
- **PDF Templates Updated**: All 6 invoice templates display item-level tax rates

### Database Enhancements
- **Invoice Audit Logs**: New `invoice_audit_logs` table for compliance tracking
- **Item Tax Rates**: Both `invoice_items` and `offer_items` support individual tax rates
- **Enhanced Seeders**: Realistic test data with mixed tax rates and audit trails

### Frontend Improvements
- **Sticky Action Buttons**: Save/cancel buttons moved to top for better UX
- **Edit Warnings**: Clear visual indicators when invoices cannot be edited
- **Audit Log Viewer**: Dialog component to view complete invoice history
- **USt. Dropdowns**: Interactive tax rate selection per line item

**Version**: 1.0.0  
**Last Updated**: February 2, 2026

