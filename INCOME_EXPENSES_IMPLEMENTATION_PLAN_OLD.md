# Income & Expenses (Einnahmen & Ausgaben) Implementation Plan

## 📋 Overview

This document outlines the plan to implement an Income & Expenses management system (Einnahmen & Ausgaben) in the invoicing system. This feature will track all business income and expenses, enabling comprehensive financial management and reporting.

---

## 🎯 Goals & Objectives

### Primary Goals
1. Track all business income (Einnahmen) - money coming in
2. Track all business expenses (Ausgaben) - money going out
3. Categorize income and expenses for better organization
4. Generate profit/loss reports (Gewinn/Verlust)
5. Integrate with existing invoice and payment system
6. Provide financial overview and analytics

### Success Criteria
- ✅ Users can create and manage income entries
- ✅ Users can create and manage expense entries
- ✅ Income/expenses are categorized and searchable
- ✅ Integration with invoices (auto-track invoice payments as income)
- ✅ Dashboard shows financial overview (income, expenses, profit)
- ✅ Reports show detailed income/expense breakdowns
- ✅ Multi-tenant support with company isolation
- ✅ German localization (Einnahmen/Ausgaben)

---

## 📐 Architecture & Design

### 1. Database Schema

#### New Table: `expense_categories`
```sql
CREATE TABLE expense_categories (
    id UUID PRIMARY KEY,
    company_id UUID NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    color VARCHAR(7) DEFAULT '#3b82f6', -- For UI display
    icon VARCHAR(50), -- Icon identifier
    is_active BOOLEAN DEFAULT true,
    sort_order INTEGER DEFAULT 0,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    
    UNIQUE(company_id, name),
    INDEX idx_company (company_id)
);
```

#### New Table: `income_categories`
```sql
CREATE TABLE income_categories (
    id UUID PRIMARY KEY,
    company_id UUID NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    color VARCHAR(7) DEFAULT '#10b981', -- Green for income
    icon VARCHAR(50),
    is_active BOOLEAN DEFAULT true,
    sort_order INTEGER DEFAULT 0,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    
    UNIQUE(company_id, name),
    INDEX idx_company (company_id)
);
```

#### New Table: `expenses` (Ausgaben)
```sql
CREATE TABLE expenses (
    id UUID PRIMARY KEY,
    company_id UUID NOT NULL,
    category_id UUID NULL, -- expense_categories
    title VARCHAR(255) NOT NULL,
    description TEXT,
    amount DECIMAL(10,2) NOT NULL,
    tax_rate DECIMAL(5,4) DEFAULT 0.19, -- 19% VAT
    tax_amount DECIMAL(10,2) DEFAULT 0,
    total_amount DECIMAL(10,2) NOT NULL, -- amount + tax
    expense_date DATE NOT NULL,
    payment_date DATE NULL, -- When actually paid
    payment_method VARCHAR(50), -- 'cash', 'bank_transfer', 'credit_card', etc.
    vendor_name VARCHAR(255), -- Supplier/vendor name
    vendor_invoice_number VARCHAR(255), -- Supplier invoice number
    receipt_file_path VARCHAR(500), -- Path to receipt/document
    status VARCHAR(50) DEFAULT 'pending', -- 'pending', 'paid', 'cancelled'
    is_recurring BOOLEAN DEFAULT false,
    recurring_frequency VARCHAR(50), -- 'monthly', 'quarterly', 'yearly'
    next_recurring_date DATE NULL,
    related_invoice_id UUID NULL, -- Link to invoice if expense related to invoice
    related_payment_id UUID NULL, -- Link to payment if created from payment
    metadata JSON NULL, -- Flexible data storage
    created_by UUID NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES expense_categories(id) ON DELETE SET NULL,
    FOREIGN KEY (related_invoice_id) REFERENCES invoices(id) ON DELETE SET NULL,
    FOREIGN KEY (related_payment_id) REFERENCES payments(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    
    INDEX idx_company_date (company_id, expense_date),
    INDEX idx_category (category_id),
    INDEX idx_status (status),
    INDEX idx_recurring (is_recurring, next_recurring_date)
);
```

#### New Table: `incomes` (Einnahmen)
```sql
CREATE TABLE incomes (
    id UUID PRIMARY KEY,
    company_id UUID NOT NULL,
    category_id UUID NULL, -- income_categories
    title VARCHAR(255) NOT NULL,
    description TEXT,
    amount DECIMAL(10,2) NOT NULL,
    tax_rate DECIMAL(5,4) DEFAULT 0.19, -- 19% VAT
    tax_amount DECIMAL(10,2) DEFAULT 0,
    total_amount DECIMAL(10,2) NOT NULL, -- amount + tax
    income_date DATE NOT NULL,
    payment_date DATE NULL, -- When actually received
    payment_method VARCHAR(50), -- 'cash', 'bank_transfer', 'credit_card', etc.
    customer_name VARCHAR(255), -- Customer name (if not from invoice)
    status VARCHAR(50) DEFAULT 'pending', -- 'pending', 'received', 'cancelled'
    is_recurring BOOLEAN DEFAULT false,
    recurring_frequency VARCHAR(50), -- 'monthly', 'quarterly', 'yearly'
    next_recurring_date DATE NULL,
    related_invoice_id UUID NULL, -- Link to invoice (auto-created from invoice payment)
    related_payment_id UUID NULL, -- Link to payment (auto-created from payment)
    metadata JSON NULL, -- Flexible data storage
    created_by UUID NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES income_categories(id) ON DELETE SET NULL,
    FOREIGN KEY (related_invoice_id) REFERENCES invoices(id) ON DELETE SET NULL,
    FOREIGN KEY (related_payment_id) REFERENCES payments(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    
    INDEX idx_company_date (company_id, income_date),
    INDEX idx_category (category_id),
    INDEX idx_status (status),
    INDEX idx_recurring (is_recurring, next_recurring_date),
    INDEX idx_invoice (related_invoice_id)
);
```

### 2. Model Structure

#### `app/Modules/Expense/Models/ExpenseCategory.php`
```php
- Relationships: company(), expenses()
- Scopes: forCompany(), active()
- Methods: getTotalExpenses(), getExpensesByPeriod()
```

#### `app/Modules/Expense/Models/Expense.php`
```php
- Relationships: 
  - company()
  - category()
  - relatedInvoice()
  - relatedPayment()
  - createdBy()
- Scopes: 
  - forCompany()
  - byCategory()
  - byStatus()
  - byDateRange()
  - recurring()
- Methods:
  - calculateTax()
  - markAsPaid()
  - createRecurring()
  - attachReceipt()
```

#### `app/Modules/Income/Models/IncomeCategory.php`
```php
- Relationships: company(), incomes()
- Scopes: forCompany(), active()
- Methods: getTotalIncome(), getIncomeByPeriod()
```

#### `app/Modules/Income/Models/Income.php`
```php
- Relationships:
  - company()
  - category()
  - relatedInvoice()
  - relatedPayment()
  - createdBy()
- Scopes:
  - forCompany()
  - byCategory()
  - byStatus()
  - byDateRange()
  - recurring()
- Methods:
  - calculateTax()
  - markAsReceived()
  - createRecurring()
  - createFromInvoicePayment() // Auto-create from invoice payment
```

### 3. Controller Structure

#### `app/Modules/Expense/Controllers/ExpenseController.php`
- `index()` - List expenses with filters
- `create()` - Show create form
- `store()` - Create new expense
- `show()` - View expense details
- `edit()` - Show edit form
- `update()` - Update expense
- `destroy()` - Delete expense
- `markAsPaid()` - Mark expense as paid
- `uploadReceipt()` - Upload receipt document

#### `app/Modules/Expense/Controllers/ExpenseCategoryController.php`
- `index()` - List categories
- `store()` - Create category
- `update()` - Update category
- `destroy()` - Delete category

#### `app/Modules/Income/Controllers/IncomeController.php`
- `index()` - List incomes with filters
- `create()` - Show create form
- `store()` - Create new income
- `show()` - View income details
- `edit()` - Show edit form
- `update()` - Update income
- `destroy()` - Delete income
- `markAsReceived()` - Mark income as received
- `createFromInvoice()` - Auto-create from invoice payment

#### `app/Modules/Income/Controllers/IncomeCategoryController.php`
- `index()` - List categories
- `store()` - Create category
- `update()` - Update category
- `destroy()` - Delete category

#### `app/Modules/Financial/Controllers/FinancialController.php` (New)
- `dashboard()` - Financial overview dashboard
- `profitLoss()` - Profit/Loss report
- `incomeExpenseReport()` - Detailed income/expense report
- `summary()` - Financial summary (totals, trends)

### 4. Policy Structure

#### `app/Modules/Expense/Policies/ExpensePolicy.php`
- `viewAny()`, `view()`, `create()`, `update()`, `delete()`

#### `app/Modules/Income/Policies/IncomePolicy.php`
- `viewAny()`, `view()`, `create()`, `update()`, `delete()`

---

## 🎨 Frontend Implementation

### 1. Pages Structure

```
resources/js/pages/
├── expenses/
│   ├── index.tsx          # List all expenses
│   ├── create.tsx         # Create new expense
│   ├── edit.tsx           # Edit expense
│   ├── show.tsx           # View expense details
│   └── categories/
│       └── index.tsx      # Manage expense categories
├── incomes/
│   ├── index.tsx          # List all incomes
│   ├── create.tsx         # Create new income
│   ├── edit.tsx           # Edit income
│   ├── show.tsx           # View income details
│   └── categories/
│       └── index.tsx      # Manage income categories
└── financial/
    ├── dashboard.tsx      # Financial overview dashboard
    ├── profit-loss.tsx    # Profit/Loss report
    └── reports.tsx        # Income/Expense reports
```

### 2. Components

```
resources/js/components/
├── expenses/
│   ├── expense-card.tsx           # Expense display card
│   ├── expense-form.tsx            # Create/edit form
│   ├── expense-filters.tsx         # Filter sidebar
│   ├── expense-category-select.tsx # Category selector
│   └── receipt-upload.tsx          # Receipt upload component
├── incomes/
│   ├── income-card.tsx            # Income display card
│   ├── income-form.tsx             # Create/edit form
│   ├── income-filters.tsx          # Filter sidebar
│   └── income-category-select.tsx  # Category selector
└── financial/
    ├── financial-summary.tsx      # Summary cards (income, expenses, profit)
    ├── profit-loss-chart.tsx      # Profit/Loss chart
    ├── income-expense-chart.tsx    # Income vs Expense chart
    └── category-breakdown.tsx      # Category breakdown chart
```

### 3. Navigation Integration

Add to `app-sidebar.tsx`:
```typescript
{
  title: "Finanzen",
  items: [
    { title: "Einnahmen", url: "/incomes", icon: TrendingUp },
    { title: "Ausgaben", url: "/expenses", icon: TrendingDown },
    { title: "Finanzübersicht", url: "/financial/dashboard", icon: BarChart3 },
    { title: "Gewinn/Verlust", url: "/financial/profit-loss", icon: Calculator },
  ]
}
```

### 4. Dashboard Integration

Add financial widgets to main dashboard:
- Total Income (Einnahmen gesamt)
- Total Expenses (Ausgaben gesamt)
- Net Profit (Gewinn/Verlust)
- Monthly comparison chart
- Recent income/expense entries

---

## 🔌 Integration Points

### 1. Invoice Integration
- **Auto-create Income**: When invoice payment is received, automatically create income entry
- **Link Income to Invoice**: Show related income in invoice view
- **Invoice as Income Source**: Track invoice payments as income

### 2. Payment Integration
- **Auto-create Income**: When payment is created for invoice, create income entry
- **Link Income to Payment**: Show income entry in payment view
- **Payment as Expense**: Track outgoing payments as expenses (if needed)

### 3. Reports Integration
- **Financial Reports**: Add income/expense data to existing reports
- **Profit/Loss Reports**: New comprehensive P&L reports
- **Tax Reports**: Income/expense data for tax reporting
- **Category Reports**: Breakdown by category

### 4. Dashboard Integration
- **Financial Overview**: Income, expenses, profit widgets
- **Trends**: Income/expense trends over time
- **Alerts**: Upcoming recurring expenses, overdue expenses

---

## 📊 Features & Functionality

### Core Features

#### 1. Expense Management (Ausgaben)
- Create, edit, delete expenses
- Categorize expenses
- Attach receipts/documents
- Track payment status (pending/paid)
- Recurring expenses support
- Tax calculation (VAT)
- Vendor/supplier tracking
- Date-based filtering

#### 2. Income Management (Einnahmen)
- Create, edit, delete income entries
- Categorize income
- Auto-create from invoice payments
- Track payment status (pending/received)
- Recurring income support
- Tax calculation (VAT)
- Customer tracking
- Date-based filtering

#### 3. Category Management
- Create custom categories for expenses
- Create custom categories for income
- Color coding for visual organization
- Icon selection
- Category-based reporting

#### 4. Financial Dashboard
- Total income display
- Total expenses display
- Net profit/loss calculation
- Monthly/yearly comparisons
- Category breakdowns
- Trend charts

#### 5. Reports & Analytics
- Profit/Loss statement
- Income by category
- Expenses by category
- Income vs Expenses comparison
- Monthly/quarterly/yearly reports
- Tax reports (for accounting)

### Advanced Features (Phase 2)

#### 1. Recurring Transactions
- Set up recurring expenses (monthly subscriptions, etc.)
- Set up recurring income (rent, etc.)
- Auto-create entries on schedule
- Manage recurring templates

#### 2. Receipt Management
- Upload and store receipts
- OCR for automatic data extraction (future)
- Receipt attachment to expenses
- Receipt gallery view

#### 3. Bank Integration (Future)
- Import bank statements
- Auto-categorize transactions
- Bank reconciliation

#### 4. Budget Management (Future)
- Set budgets for categories
- Budget vs actual tracking
- Budget alerts

---

## 🗂️ File Structure

### Backend
```
app/Modules/
├── Expense/
│   ├── Controllers/
│   │   ├── ExpenseController.php
│   │   └── ExpenseCategoryController.php
│   ├── Models/
│   │   ├── Expense.php
│   │   └── ExpenseCategory.php
│   └── Policies/
│       └── ExpensePolicy.php
├── Income/
│   ├── Controllers/
│   │   ├── IncomeController.php
│   │   └── IncomeCategoryController.php
│   ├── Models/
│   │   ├── Income.php
│   │   └── IncomeCategory.php
│   └── Policies/
│       └── IncomePolicy.php
└── Financial/
    └── Controllers/
        └── FinancialController.php
```

### Frontend
```
resources/js/
├── pages/
│   ├── expenses/
│   │   ├── index.tsx
│   │   ├── create.tsx
│   │   ├── edit.tsx
│   │   ├── show.tsx
│   │   └── categories/
│   │       └── index.tsx
│   ├── incomes/
│   │   ├── index.tsx
│   │   ├── create.tsx
│   │   ├── edit.tsx
│   │   ├── show.tsx
│   │   └── categories/
│   │       └── index.tsx
│   └── financial/
│       ├── dashboard.tsx
│       ├── profit-loss.tsx
│       └── reports.tsx
└── components/
    ├── expenses/
    │   ├── expense-card.tsx
    │   ├── expense-form.tsx
    │   └── expense-filters.tsx
    ├── incomes/
    │   ├── income-card.tsx
    │   ├── income-form.tsx
    │   └── income-filters.tsx
    └── financial/
        ├── financial-summary.tsx
        └── profit-loss-chart.tsx
```

### Database
```
database/migrations/
├── YYYY_MM_DD_create_expense_categories_table.php
├── YYYY_MM_DD_create_income_categories_table.php
├── YYYY_MM_DD_create_expenses_table.php
└── YYYY_MM_DD_create_incomes_table.php
```

### Routes
```
routes/modules/
├── expenses.php
├── incomes.php
└── financial.php
```

---

## 🔐 Permissions

### New Permissions
- `manage_expenses` - Full CRUD access to expenses
- `view_expenses` - View expenses (read-only)
- `manage_incomes` - Full CRUD access to incomes
- `view_incomes` - View incomes (read-only)
- `view_financial_reports` - Access to financial reports

### Role Assignments
- **Super Admin**: All permissions
- **Admin**: All financial permissions
- **User**: `view_expenses`, `view_incomes`, `view_financial_reports` (may need approval for create)

---

## 📝 Implementation Phases

### Phase 1: Core Foundation (Week 1-2)
- [ ] Database migrations (expenses, incomes, categories)
- [ ] Models and relationships
- [ ] Basic CRUD controllers
- [ ] Policy implementation
- [ ] Basic frontend pages (index, create, edit, show)
- [ ] Category management
- [ ] Navigation integration
- [ ] Multi-tenancy support

### Phase 2: Integration & Automation (Week 2-3)
- [ ] Auto-create income from invoice payments
- [ ] Link income to invoices/payments
- [ ] Dashboard widgets
- [ ] Basic financial summary
- [ ] Tax calculation
- [ ] Receipt upload functionality

### Phase 3: Reports & Analytics (Week 3-4)
- [ ] Profit/Loss report
- [ ] Income/Expense reports
- [ ] Category breakdown reports
- [ ] Charts and visualizations
- [ ] Date range filtering
- [ ] Export functionality (PDF, CSV)

### Phase 4: Advanced Features (Week 4-5)
- [ ] Recurring transactions
- [ ] Recurring templates
- [ ] Advanced filtering
- [ ] Bulk operations
- [ ] Financial dashboard enhancements

### Phase 5: Polish & Testing (Week 5)
- [ ] UI/UX improvements
- [ ] Comprehensive testing
- [ ] Documentation
- [ ] Performance optimization
- [ ] German localization review

---

## 🧪 Testing Strategy

### Unit Tests
- Expense model tests
- Income model tests
- Category model tests
- Controller tests
- Policy tests

### Feature Tests
- Expense CRUD operations
- Income CRUD operations
- Category management
- Auto-income creation from payments
- Financial calculations
- Multi-tenancy isolation
- Permission enforcement

### Integration Tests
- Invoice payment → Income creation
- Payment → Income linking
- Financial dashboard data
- Reports generation
- Tax calculations

---

## 📚 Documentation

### User Documentation
- How to create expenses
- How to create income entries
- How to manage categories
- How to view financial reports
- How recurring transactions work
- How to attach receipts

### Developer Documentation
- Model structure
- API endpoints
- Integration guide
- Auto-income creation logic
- Financial calculation formulas

---

## 🎯 Key Considerations

### 1. Auto-Income Creation
When an invoice payment is received:
- Automatically create income entry
- Link to invoice and payment
- Use invoice amount as income amount
- Set category based on invoice or default
- Set income date = payment date

### 2. Tax Handling
- Support VAT (Mehrwertsteuer) calculation
- Configurable tax rates per company
- Track tax amounts separately
- Support tax-exempt transactions

### 3. Currency
- Currently assumes EUR (€)
- Consider multi-currency support (future)

### 4. Accounting Integration
- Consider export formats for accounting software
- Support common accounting standards
- Date-based reporting for tax periods

### 5. Data Privacy
- Financial data is sensitive
- Ensure proper access controls
- Audit logging for financial transactions

---

## 📌 Next Steps

1. **Review & Approve Plan**
   - Review this plan with stakeholders
   - Confirm scope and priorities
   - Adjust timeline if needed

2. **Begin Phase 1**
   - Start with database migrations
   - Create models and basic CRUD
   - Build foundation before advanced features

3. **Iterative Development**
   - Follow agile approach
   - Regular reviews and adjustments
   - Test as we build

---

## 📊 Example Use Cases

### Use Case 1: Track Office Rent Expense
1. User creates expense: "Büromiete Januar 2025"
2. Category: "Miete" (Rent)
3. Amount: €1,500.00
4. Tax: 19% VAT = €285.00
5. Total: €1,785.00
6. Date: 2025-01-01
7. Status: Paid
8. Payment method: Bank transfer

### Use Case 2: Auto-Create Income from Invoice Payment
1. Customer pays invoice RE-2025-001 (€2,000.00)
2. System automatically creates income entry:
   - Title: "Rechnung RE-2025-001"
   - Amount: €2,000.00
   - Category: "Rechnungen" (Invoices)
   - Linked to invoice and payment
   - Date: Payment date

### Use Case 3: Monthly Recurring Expense
1. User creates expense: "Netflix Abo"
2. Sets as recurring: Monthly
3. System creates entry each month automatically
4. User can manage recurring template

### Use Case 4: Financial Overview
1. User views financial dashboard
2. Sees:
   - Total Income: €50,000.00
   - Total Expenses: €30,000.00
   - Net Profit: €20,000.00
3. Views breakdown by category
4. Compares month-over-month

---

## ✅ Checklist

### Database
- [ ] Create expense_categories table
- [ ] Create income_categories table
- [ ] Create expenses table
- [ ] Create incomes table
- [ ] Add indexes for performance
- [ ] Add foreign key constraints

### Backend
- [ ] Create Expense model
- [ ] Create Income model
- [ ] Create category models
- [ ] Create controllers
- [ ] Create policies
- [ ] Create request validators
- [ ] Implement auto-income creation
- [ ] Implement tax calculations

### Frontend
- [ ] Create expense pages
- [ ] Create income pages
- [ ] Create category management
- [ ] Create financial dashboard
- [ ] Create reports pages
- [ ] Create components
- [ ] Add navigation items

### Integration
- [ ] Integrate with invoice payments
- [ ] Integrate with payment system
- [ ] Add dashboard widgets
- [ ] Add to reports module

### Testing
- [ ] Unit tests
- [ ] Feature tests
- [ ] Integration tests
- [ ] UI tests

### Documentation
- [ ] User guide
- [ ] Developer docs
- [ ] API documentation

---

**Ready to start implementation!** 🚀

