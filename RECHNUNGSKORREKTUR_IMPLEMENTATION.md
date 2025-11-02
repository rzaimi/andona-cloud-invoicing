# Rechnungskorrektur (Invoice Correction) Implementation

## ✅ Completed Implementation

AndoBill now supports the **German Rechnungskorrektur** (Stornorechnung) process, ensuring compliance with German tax and accounting regulations.

---

## 🇩🇪 German Tax Compliance

### Legal Requirements

In Germany, **once an invoice is sent, it cannot be simply deleted or edited**. Instead, you must:

1. **Create a Stornorechnung** (cancellation invoice) with negative amounts
2. **Link** the Stornorechnung to the original invoice
3. **Keep both invoices** in the system for audit trail
4. **Optionally create a new correct invoice** if needed

This implementation follows § 14 UStG (German VAT law) requirements.

---

## 🎯 Features Implemented

### 1. **Database Schema**
- ✅ Added correction tracking fields to `invoices` table:
  - `is_correction` - Marks if this is a correction invoice
  - `corrects_invoice_id` - References the original invoice being corrected
  - `corrected_by_invoice_id` - References the correction invoice (if corrected)
  - `correction_reason` - Reason for the correction
  - `corrected_at` - Timestamp when corrected

### 2. **Invoice Model Enhancements**
- ✅ Added correction relationships:
  - `correctsInvoice()` - The original invoice that this corrects
  - `correctedByInvoice()` - The correction that cancels this invoice
- ✅ Added helper methods:
  - `canBeCorrect()` - Check if invoice can be corrected
  - `isCorrected()` - Check if invoice has been corrected
  - `generateCorrectionNumber()` - Generate Stornorechnung number

### 3. **Business Logic**
- ✅ **Automatic Stornorechnung Creation**:
  - Copies all line items with negative quantities
  - Sets negative amounts (subtotal, tax, total)
  - Links to original invoice
  - Marks original as "cancelled"
  - Generates proper invoice number (e.g., `RE-STORNO-2024-001`)

### 4. **Frontend UI**
- ✅ **Correction Dialog**:
  - Professional modal with correction reason input
  - Clear warning about German tax requirements
  - Explanation of what will happen
  - Validation and error handling
  
- ✅ **"Stornieren" Button**:
  - Shows only for sent/paid/overdue invoices
  - Hidden if already corrected
  - Hidden if it's already a correction
  - Prominent red/destructive styling

---

## 🚀 How to Use

### Creating a Stornorechnung

1. **Open an existing invoice** (that has been sent/paid)
2. **Click the "Stornieren" button** (red button in the top right)
3. **Enter a reason** for the correction (required)
   - Examples: "Fehler in der Rechnungsstellung", "Kunde hat storniert", "Preisfehler"
4. **Click "Stornorechnung erstellen"**

### What Happens:

✅ **Original Invoice** (`RE-2024-001`):
- Status changed to "Cancelled"
- Linked to the Stornorechnung
- Marked with `corrected_by_invoice_id`
- Preserved in system for audit

✅ **Stornorechnung** (`RE-STORNO-2024-001`):
- New invoice created
- All items with **negative quantities**
- **Negative totals** (cancels the original)
- References original invoice
- Status: "Sent"
- Contains correction reason in notes

---

## 📋 Invoice Number Format

**Original Invoice:**
```
RE-2024-001
```

**Stornorechnung:**
```
RE-STORNO-2024-001
```

The system automatically prefixes "STORNO-" to maintain clear audit trail.

---

## 🔒 Security & Compliance

### Restrictions
- ❌ **Cannot correct draft invoices** - only sent/paid/overdue
- ❌ **Cannot correct an already corrected invoice** - prevents double corrections
- ❌ **Cannot correct a Stornorechnung** - prevents correction loops

### Audit Trail
- ✅ **Both invoices preserved** in database
- ✅ **Bidirectional linking** (original ↔ correction)
- ✅ **Reason documented** for every correction
- ✅ **Timestamp recorded** when correction was made
- ✅ **Complete history** available for tax audits

---

## 💡 Best Practices

### When to Use Stornorechnung

**Use for:**
- ✅ Incorrect amounts or prices
- ✅ Wrong customer billing
- ✅ Cancelled orders after invoice sent
- ✅ Any error in a sent invoice

**Don't use for:**
- ❌ Draft invoices (just edit or delete them)
- ❌ Unpaid invoices that haven't been sent yet

### Workflow Recommendation

1. **Create Stornorechnung** to cancel the incorrect invoice
2. **Create new correct invoice** with the right information
3. **Send both to customer** (Stornorechnung + new invoice)
4. **Customer sees:** Original cancelled, new correct invoice to pay

---

## 🎨 UI/UX Features

### Visual Indicators
- **Red "Stornieren" button** - Clear destructive action
- **Warning alert** - Explains German tax requirements
- **Clear explanation** - Users understand what will happen
- **Correction badge** - Shows correction status on invoice list

### User Experience
- **One-click correction** - Simple workflow
- **Required reason** - Ensures documentation
- **Immediate feedback** - Success/error messages
- **Automatic redirect** - Goes to newly created Stornorechnung

---

## 📊 Database Structure

```sql
invoices
├── is_correction (boolean)
├── corrects_invoice_id (uuid, nullable)
├── corrected_by_invoice_id (uuid, nullable)
├── correction_reason (text, nullable)
└── corrected_at (timestamp, nullable)
```

### Relationships
```
Original Invoice (RE-2024-001)
    └── corrected_by_invoice_id → Stornorechnung (RE-STORNO-2024-001)
    
Stornorechnung (RE-STORNO-2024-001)
    └── corrects_invoice_id → Original Invoice (RE-2024-001)
```

---

## ✅ Implementation Checklist

**All tasks completed:**
1. ✅ Database migration for correction fields
2. ✅ Invoice model relationships and methods
3. ✅ Controller method for creating corrections
4. ✅ Route for correction endpoint
5. ✅ Correction dialog component
6. ✅ "Stornieren" button on invoice edit page
7. ✅ Frontend build completed

**Status:** 🎉 **Production Ready!**

---

## 📝 Example Scenario

### Before Correction:
```
Invoice: RE-2024-001
Customer: Max Mustermann GmbH
Amount: 1.190,00 €
Status: Sent
```

### After Correction:
```
Original Invoice: RE-2024-001
Status: Cancelled ❌
Corrected by: RE-STORNO-2024-001

Stornorechnung: RE-STORNO-2024-001
Customer: Max Mustermann GmbH  
Amount: -1.190,00 € (negative)
Status: Sent ✓
Reason: "Fehler in der Preisberechnung"
```

---

## 🚀 Next Steps (Optional Future Enhancements)

1. **Correction History View** - Dedicated page showing all corrections
2. **Bulk Corrections** - Correct multiple invoices at once
3. **Partial Corrections** - Correct only specific line items
4. **Email Templates** - Automatic email to customer with Stornorechnung
5. **Reporting** - Correction analytics and reports
6. **PDF Watermark** - Mark corrected invoices visually in PDF

---

Built with ❤️ for AndoBill following German tax regulations (§ 14 UStG).

