# AndoBill - Multi-Tenant Invoicing System

A comprehensive, modern invoicing and billing system built with Laravel 12 and React 19. Designed for multi-tenant use with role-based access control, supporting invoices, offers, customers, products, and more.

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
- **Laravel 12** - PHP framework
- **PHP 8.2+** - Programming language
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

- PHP 8.2 or higher
- Composer
- Node.js 20.19.0+ or 22.12.0+ (Node.js 21.x is not supported)
- npm 10+
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

See [ROLES_AND_PERMISSIONS.md](ROLES_AND_PERMISSIONS.md) for detailed permissions.

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

- [Company Setup Wizard](COMPANY_WIZARD.md)
- [E-Rechnung Implementation](E-RECHNUNG_IMPLEMENTATION.md)
- [Reminder System](MAHNUNG_SYSTEM.md)
- [Roles & Permissions](ROLES_AND_PERMISSIONS.md)
- [Invoice Corrections](RECHNUNGSKORREKTUR_IMPLEMENTATION.md)

## 🔒 Security

- CSRF protection enabled
- SQL injection protection via Eloquent ORM
- XSS protection via Blade templating
- Authentication required for all routes
- Role-based access control
- Company data isolation

## 🚀 Production Deployment

### Node.js Version Management

This project requires **Node.js 20.19.0+ or 22.12.0+**. Node.js 21.x is not supported.

If using `nodenv` or `nvm`:

```bash
# Using nodenv
nodenv install 20.19.0
nodenv local 20.19.0

# Using nvm
nvm install 20.19.0
nvm use 20.19.0
```

The project includes `.nvmrc` and `.node-version` files to help version managers automatically select the correct Node.js version.

### Installation on Hosting

```bash
# Make sure you're using the correct Node.js version
node -v  # Should be 20.19.0+ or 22.12.0+

# Install dependencies
npm ci

# Build assets
npm run build
```

**Troubleshooting npm install errors:**

If you encounter engine warnings or `nodenv: node: command not found` errors:

1. **Switch Node.js version** (recommended):
   ```bash
   nodenv local 20.19.0  # or nvm use 20.19.0
   ```

2. **Fix PATH for nodenv** (if node command not found):
   ```bash
   eval "$(nodenv init -)"
   export PATH="$HOME/.nodenv/bin:$PATH"
   ```

3. **Workaround** (if you must use unsupported Node version):
   ```bash
   npm install --ignore-engines
   ```
   ⚠️ **Warning**: This may cause runtime issues. Use proper Node.js version instead.

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

**Version**: 1.0.0  
**Last Updated**: November 2024

