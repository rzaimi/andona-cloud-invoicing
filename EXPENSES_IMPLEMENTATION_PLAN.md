# Expenses (Ausgaben) Implementation Plan

## 📋 Overview

This document outlines the plan to implement an Expenses management system (Ausgaben) in the invoicing system. **Income will be derived from paid invoices** (which are already tracked), so we only need to implement expense tracking. This simplifies the system and avoids duplication.

---

## 🎯 Goals & Objectives

### Primary Goals
1. Track all business expenses (Ausgaben) - money going out
2. Categorize expenses for better organization
3. Generate profit/loss reports (Gewinn/Verlust) using:
   - **Income**: Paid invoices (already tracked)
   - **Expenses**: New expense entries
4. Provide financial overview and analytics
5. Integrate with existing invoice and payment system

### Success Criteria
- ✅ Users can create and manage expense entries
- ✅ Expenses are categorized and searchable
- ✅ Dashboard shows financial overview (income from invoices, expenses, profit)
- ✅ Reports show detailed expense breakdowns and profit/loss
- ✅ Multi-tenant support with company isolation
- ✅ German localization (Ausgaben)

### Why No Separate Income Module?
- **Paid invoices = Income**: When invoices are paid, that money is income
- **Already tracked**: The system already tracks invoice payments
- **Avoid duplication**: No need to duplicate data that already exists
- **Simpler system**: Less complexity, easier to maintain
- **Single source of truth**: Invoices are the source of income

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
    metadata JSON NULL, -- Flexible data storage
    created_by UUID NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES expense_categories(id) ON DELETE SET NULL,
    FOREIGN KEY (related_invoice_id) REFERENCES invoices(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    
    INDEX idx_company_date (company_id, expense_date),
    INDEX idx_category (category_id),
    INDEX idx_status (status),
    INDEX idx_recurring (is_recurring, next_recurring_date)
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
  - createdBy()
- Scopes: 
  - forCompany()
  - byCategory()
  - byStatus()
  - byDateRange()
  - recurring()
  - paid()
  - pending()
- Methods:
  - calculateTax()
  - markAsPaid()
  - createRecurring()
  - attachReceipt()
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

#### `app/Modules/Financial/Controllers/FinancialController.php` (New)
- `dashboard()` - Financial overview dashboard
  - Income: Sum of paid invoices
  - Expenses: Sum of expenses
  - Profit: Income - Expenses
- `profitLoss()` - Profit/Loss report
  - Income from paid invoices by period
  - Expenses by period
  - Net profit/loss
- `expenseReport()` - Detailed expense report
- `summary()` - Financial summary (totals, trends)

### 4. Policy Structure

#### `app/Modules/Expense/Policies/ExpensePolicy.php`
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
└── financial/
    ├── dashboard.tsx      # Financial overview dashboard
    ├── profit-loss.tsx    # Profit/Loss report
    └── reports.tsx        # Expense reports
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
└── financial/
    ├── financial-summary.tsx      # Summary cards (income from invoices, expenses, profit)
    ├── profit-loss-chart.tsx      # Profit/Loss chart
    ├── income-expense-chart.tsx    # Income (invoices) vs Expense chart
    └── category-breakdown.tsx      # Expense category breakdown chart
```

### 3. Navigation Integration

Add to `app-sidebar.tsx`:
```typescript
{
  title: "Finanzen",
  items: [
    { title: "Ausgaben", url: "/expenses", icon: TrendingDown },
    { title: "Finanzübersicht", url: "/financial/dashboard", icon: BarChart3 },
    { title: "Gewinn/Verlust", url: "/financial/profit-loss", icon: Calculator },
  ]
}
```

**Note**: No "Einnahmen" menu item needed - income is tracked via invoices.

### 4. Dashboard Integration

Add financial widgets to main dashboard:
- **Total Income** (Einnahmen gesamt) - Sum of paid invoices
- **Total Expenses** (Ausgaben gesamt) - Sum of expenses
- **Net Profit** (Gewinn/Verlust) - Income - Expenses
- Monthly comparison chart
- Recent expenses

---

## 🔌 Integration Points

### 1. Invoice Integration
- **Link Expenses to Invoices**: Optional link if expense is related to specific invoice
- **Show Expenses in Invoice View**: Display related expenses (if any)
- **Income Source**: Use paid invoices as income source in financial reports

### 2. Payment Integration
- **Income Calculation**: Sum of payments for paid invoices = Income
- **Financial Reports**: Use payment data to calculate income

### 3. Reports Integration
- **Financial Reports**: Combine paid invoices (income) with expenses
- **Profit/Loss Reports**: Income (from invoices) - Expenses
- **Tax Reports**: Expense data for tax reporting
- **Category Reports**: Expense breakdown by category

### 4. Dashboard Integration
- **Financial Overview**: 
  - Income from paid invoices
  - Expenses
  - Profit (Income - Expenses)
- **Trends**: Income (invoices) vs expenses trends over time
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

#### 2. Category Management
- Create custom categories for expenses
- Color coding for visual organization
- Icon selection
- Category-based reporting

#### 3. Financial Dashboard
- **Income Display**: Sum of paid invoices
- **Expenses Display**: Sum of expenses
- **Net Profit/Loss**: Income - Expenses
- Monthly/yearly comparisons
- Category breakdowns
- Trend charts

#### 4. Reports & Analytics
- **Profit/Loss Statement**:
  - Income: Paid invoices by period
  - Expenses: Expenses by period
  - Net: Income - Expenses
- **Expense Reports**:
  - Expenses by category
  - Expenses by vendor
  - Expenses by date range
- **Tax Reports**: Expense data for accounting

### Advanced Features (Phase 2)

#### 1. Recurring Expenses
- Set up recurring expenses (monthly subscriptions, etc.)
- Auto-create entries on schedule
- Manage recurring templates

#### 2. Receipt Management
- Upload and store receipts
- OCR for automatic data extraction (future)
- Receipt attachment to expenses
- Receipt gallery view

#### 3. Budget Management (Future)
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
│   └── financial/
│       ├── dashboard.tsx
│       ├── profit-loss.tsx
│       └── reports.tsx
└── components/
    ├── expenses/
    │   ├── expense-card.tsx
    │   ├── expense-form.tsx
    │   └── expense-filters.tsx
    └── financial/
        ├── financial-summary.tsx
        └── profit-loss-chart.tsx
```

### Database
```
database/migrations/
├── YYYY_MM_DD_create_expense_categories_table.php
└── YYYY_MM_DD_create_expenses_table.php
```

### Routes
```
routes/modules/
├── expenses.php
└── financial.php
```

---

## 🔐 Permissions

### New Permissions
- `manage_expenses` - Full CRUD access to expenses
- `view_expenses` - View expenses (read-only)
- `view_financial_reports` - Access to financial reports

### Role Assignments
- **Super Admin**: All permissions
- **Admin**: All financial permissions
- **User**: `view_expenses`, `view_financial_reports` (may need approval for create)

---

## 📝 Implementation Phases

### Phase 1: Core Foundation (Week 1-2)
- [ ] Database migrations (expenses, categories)
- [ ] Models and relationships
- [ ] Basic CRUD controllers
- [ ] Policy implementation
- [ ] Basic frontend pages (index, create, edit, show)
- [ ] Category management
- [ ] Navigation integration
- [ ] Multi-tenancy support

### Phase 2: Integration & Financial Dashboard (Week 2-3)
- [ ] Financial dashboard (income from invoices, expenses, profit)
- [ ] Link expenses to invoices (optional)
- [ ] Dashboard widgets
- [ ] Basic financial summary
- [ ] Tax calculation
- [ ] Receipt upload functionality

### Phase 3: Reports & Analytics (Week 3-4)
- [ ] Profit/Loss report (income from invoices - expenses)
- [ ] Expense reports
- [ ] Category breakdown reports
- [ ] Charts and visualizations
- [ ] Date range filtering
- [ ] Export functionality (PDF, CSV)

### Phase 4: Advanced Features (Week 4-5)
- [ ] Recurring expenses
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

## 💡 Income Calculation Logic

### How Income is Calculated

```php
// In FinancialController or FinancialService

public function getIncome($companyId, $startDate = null, $endDate = null)
{
    $query = Payment::forCompany($companyId)
        ->where('status', 'completed')
        ->whereHas('invoice', function($q) {
            $q->where('status', 'paid');
        });
    
    if ($startDate) {
        $query->where('payment_date', '>=', $startDate);
    }
    
    if ($endDate) {
        $query->where('payment_date', '<=', $endDate);
    }
    
    return $query->sum('amount');
}

// Or from invoices directly:
public function getIncomeFromInvoices($companyId, $startDate = null, $endDate = null)
{
    $query = Invoice::forCompany($companyId)
        ->where('status', 'paid');
    
    if ($startDate) {
        $query->where('issue_date', '>=', $startDate);
    }
    
    if ($endDate) {
        $query->where('issue_date', '<=', $endDate);
    }
    
    return $query->sum('total');
}
```

### Profit/Loss Calculation

```php
public function getProfitLoss($companyId, $startDate = null, $endDate = null)
{
    $income = $this->getIncome($companyId, $startDate, $endDate);
    $expenses = Expense::forCompany($companyId)
        ->where('status', 'paid')
        ->when($startDate, fn($q) => $q->where('expense_date', '>=', $startDate))
        ->when($endDate, fn($q) => $q->where('expense_date', '<=', $endDate))
        ->sum('total_amount');
    
    return [
        'income' => $income,
        'expenses' => $expenses,
        'profit' => $income - $expenses,
    ];
}
```

---

## 🎯 Key Considerations

### 1. Income Source
- **Primary**: Paid invoices (via payments)
- **Alternative**: Could also use invoice totals where status = 'paid'
- **Single source**: Use one consistent method throughout

### 2. Tax Handling
- Support VAT (Mehrwertsteuer) calculation for expenses
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

### Use Case 2: View Financial Overview
1. User views financial dashboard
2. Sees:
   - **Total Income**: €50,000.00 (from paid invoices)
   - **Total Expenses**: €30,000.00
   - **Net Profit**: €20,000.00
3. Views breakdown by category
4. Compares month-over-month

### Use Case 3: Monthly Recurring Expense
1. User creates expense: "Netflix Abo"
2. Sets as recurring: Monthly
3. System creates entry each month automatically
4. User can manage recurring template

### Use Case 4: Profit/Loss Report
1. User generates P&L report for Q1 2025
2. Report shows:
   - **Income**: €150,000 (sum of paid invoices in Q1)
   - **Expenses**: €80,000 (sum of expenses in Q1)
   - **Net Profit**: €70,000
3. Breakdown by category
4. Comparison to previous period

---

## ✅ Checklist

### Database
- [ ] Create expense_categories table
- [ ] Create expenses table
- [ ] Add indexes for performance
- [ ] Add foreign key constraints

### Backend
- [ ] Create Expense model
- [ ] Create ExpenseCategory model
- [ ] Create controllers
- [ ] Create policies
- [ ] Create request validators
- [ ] Implement tax calculations
- [ ] Create FinancialController for income/expense calculations

### Frontend
- [ ] Create expense pages
- [ ] Create category management
- [ ] Create financial dashboard
- [ ] Create reports pages
- [ ] Create components
- [ ] Add navigation items

### Integration
- [ ] Financial calculations (income from invoices)
- [ ] Dashboard widgets
- [ ] Reports integration

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

## 🚀 Benefits of This Approach

1. **Simpler System**: No duplicate income tracking
2. **Single Source of Truth**: Invoices are the income source
3. **Less Maintenance**: Fewer tables, less code
4. **Consistent Data**: Income always matches paid invoices
5. **Easier to Understand**: Clear relationship between invoices and income
6. **Faster Development**: Less to build and test

---

**Ready to start implementation!** 🚀

