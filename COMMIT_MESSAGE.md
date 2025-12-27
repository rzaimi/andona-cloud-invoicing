fix: Improve PDF layouts and fix stdClass property access errors

## Layout Improvements

### Invoice & Offer PDF Layouts
- **Improved spacing and margins**: Increased container padding (15-20mm) for better breathing room
- **Better visual hierarchy**: Standardized spacing between sections (15-20px instead of 8-10px)
- **Enhanced readability**: Improved line-height (1.6 instead of 1.4-1.5) for better text flow
- **Consistent spacing**: Unified spacing across all templates for professional appearance

### Template-Specific Updates
- **Clean template**: Improved header padding and section spacing
- **Modern template**: Better info box padding and visual spacing
- **Offer layout**: Reduced excessive margins (40px → 25px) for better balance

## Bug Fixes

### stdClass Property Access
- **Fixed `getCompanySnapshot()` error**: Added safe property access for DomPDF-converted models
- **Fixed `is_correction` property error**: Added isset() checks in all invoice templates
- **Fixed `correction_reason` property error**: Added safe property access
- **Fixed `correctsInvoice` relationship**: Added proper null checks

### EmailLog Model
- **Created missing EmailLog model**: Added complete model with relationships, scopes, and accessors
- **Fixed autoload error**: Resolved "Failed to open stream" error for EmailLog class

## Files Changed

### Layout Improvements
- `resources/views/pdf/invoice.blade.php` - Improved container padding and spacing
- `resources/views/pdf/offer.blade.php` - Reduced excessive margins and improved spacing
- `resources/views/pdf/invoice-templates/clean.blade.php` - Enhanced spacing throughout
- `resources/views/pdf/invoice-templates/modern.blade.php` - Better visual hierarchy

### Bug Fixes
- `resources/views/pdf/invoice.blade.php` - Safe snapshot access for stdClass objects
- `resources/views/pdf/offer.blade.php` - Safe snapshot access for stdClass objects
- `resources/views/pdf/invoice-templates/*.blade.php` - Safe property access for is_correction, correction_reason
- `app/Models/EmailLog.php` - Created missing model file

## Technical Details

### Safe Property Access Pattern
```php
// Handle both model instance and stdClass/array (DomPDF may convert models)
if (is_object($invoice) && method_exists($invoice, 'getCompanySnapshot')) {
    $snapshot = $invoice->getCompanySnapshot();
} elseif (isset($invoice->company_snapshot) && is_array($invoice->company_snapshot)) {
    $snapshot = $invoice->company_snapshot;
} // ... fallback logic
```

### Layout Spacing Standards
- Container padding: 15-20mm (was 10-15mm)
- Section spacing: 15-20px (was 8-10px)
- Line height: 1.6 (was 1.4-1.5)
- Table margins: 15px (was 10px)

## Benefits
✅ **Better visual appearance**: More professional and balanced layouts
✅ **No more errors**: Fixed all stdClass property access issues
✅ **Consistent spacing**: Unified spacing across all templates
✅ **Better readability**: Improved line-height and spacing
✅ **Complete model**: EmailLog model now available for email logging

## Testing
- Verified PDF generation works without errors
- Confirmed safe property access handles all object types
- Tested layout improvements render correctly
- Verified EmailLog model loads correctly
