<?php

namespace App\Http\Controllers;

use App\Modules\Customer\Models\Customer;
use App\Modules\Product\Models\Product;
use App\Modules\Product\Models\Category;
use App\Modules\Invoice\Models\Invoice;
use App\Modules\Invoice\Models\InvoiceItem;
use App\Modules\User\Models\User;
use App\Services\ContextService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class ImportController extends Controller
{
    public function __construct(ContextService $contextService)
    {
        parent::__construct($contextService);
        $this->middleware('auth');
    }

    /**
     * Show import/export page in settings (admin only)
     */
    public function showImportExportPage(Request $request)
    {
        return Inertia::render('settings/import-export');
    }

    /**
     * Import customers from CSV
     */
    public function importCustomers(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:10240', // 10MB max
        ]);

        $companyId = $this->getEffectiveCompanyId();
        $file = $request->file('file');
        
        $errors = [];
        $success = [];
        $skipped = [];
        
        $handle = fopen($file->getRealPath(), 'r');
        
        // Skip UTF-8 BOM if present
        $firstLine = fgets($handle);
        if (substr($firstLine, 0, 3) === "\xEF\xBB\xBF") {
            rewind($handle);
            fseek($handle, 3);
        } else {
            rewind($handle);
        }
        
        // Read header row
        $header = fgetcsv($handle, 0, ';');
        if (!$header) {
            return back()->withErrors(['file' => 'Die CSV-Datei ist leer oder ungültig.']);
        }
        
        // Map German headers to English field names
        $headerMap = [
            'Nummer' => 'number',
            'Name' => 'name',
            'E-Mail' => 'email',
            'Telefon' => 'phone',
            'Adresse' => 'address',
            'PLZ' => 'postal_code',
            'Stadt' => 'city',
            'Land' => 'country',
            'Steuernummer' => 'tax_number',
            'USt-IdNr.' => 'vat_number',
            'Ansprechpartner' => 'contact_person',
            'Typ' => 'customer_type',
            'Status' => 'status',
        ];
        
        $mappedHeaders = [];
        foreach ($header as $index => $col) {
            $col = trim($col);
            if (isset($headerMap[$col])) {
                $mappedHeaders[$index] = $headerMap[$col];
            }
        }
        
        $rowNumber = 1;
        
        DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle, 0, ';')) !== false) {
                $rowNumber++;
                
                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }
                
                $data = [];
                foreach ($mappedHeaders as $index => $field) {
                    $data[$field] = isset($row[$index]) ? trim($row[$index]) : null;
                }
                
                // Skip if no name or email
                if (empty($data['name']) && empty($data['email'])) {
                    $skipped[] = "Zeile {$rowNumber}: Kein Name oder E-Mail angegeben";
                    continue;
                }
                
                // Validate data
                $validator = Validator::make($data, [
                    'email' => 'nullable|email',
                    'customer_type' => 'nullable|in:business,private',
                    'status' => 'nullable|in:active,inactive',
                ]);
                
                if ($validator->fails()) {
                    $errors[] = "Zeile {$rowNumber}: " . implode(', ', $validator->errors()->all());
                    continue;
                }
                
                // Check if customer already exists (by number or email)
                $existing = Customer::forCompany($companyId)
                    ->where(function ($q) use ($data) {
                        if (!empty($data['number'])) {
                            $q->where('number', $data['number']);
                        }
                        if (!empty($data['email'])) {
                            $q->orWhere('email', $data['email']);
                        }
                    })
                    ->first();
                
                if ($existing) {
                    $skipped[] = "Zeile {$rowNumber}: Kunde existiert bereits ({$data['number']} oder {$data['email']})";
                    continue;
                }
                
                // Set defaults
                $data['company_id'] = $companyId;
                $data['customer_type'] = $data['customer_type'] ?? 'business';
                $data['status'] = $data['status'] ?? 'active';
                
                // Create customer
                $customer = Customer::create($data);
                $success[] = "Zeile {$rowNumber}: Kunde '{$customer->name}' erfolgreich importiert";
            }
            
            DB::commit();
            
            fclose($handle);
            
            $message = count($success) . ' Kunden erfolgreich importiert.';
            if (count($skipped) > 0) {
                $message .= ' ' . count($skipped) . ' Zeilen übersprungen.';
            }
            if (count($errors) > 0) {
                $message .= ' ' . count($errors) . ' Fehler aufgetreten.';
            }
            
            return back()->with('success', $message)
                ->with('import_details', [
                    'success' => $success,
                    'skipped' => $skipped,
                    'errors' => $errors,
                ]);
                
        } catch (\Exception $e) {
            DB::rollBack();
            fclose($handle);
            
            return back()->withErrors(['file' => 'Fehler beim Import: ' . $e->getMessage()]);
        }
    }

    /**
     * Import products from CSV
     */
    public function importProducts(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:10240', // 10MB max
        ]);

        $companyId = $this->getEffectiveCompanyId();
        $file = $request->file('file');
        
        $errors = [];
        $success = [];
        $skipped = [];
        
        $handle = fopen($file->getRealPath(), 'r');
        
        // Skip UTF-8 BOM if present
        $firstLine = fgets($handle);
        if (substr($firstLine, 0, 3) === "\xEF\xBB\xBF") {
            rewind($handle);
            fseek($handle, 3);
        } else {
            rewind($handle);
        }
        
        // Read header row
        $header = fgetcsv($handle, 0, ';');
        if (!$header) {
            return back()->withErrors(['file' => 'Die CSV-Datei ist leer oder ungültig.']);
        }
        
        // Map German headers to English field names
        $headerMap = [
            'Nummer' => 'number',
            'Name' => 'name',
            'Beschreibung' => 'description',
            'Kategorie' => 'category_name',
            'Artikelnummer (SKU)' => 'sku',
            'Barcode' => 'barcode',
            'Einheit' => 'unit',
            'Preis' => 'price',
            'Einkaufspreis' => 'cost_price',
            'Steuersatz (%)' => 'tax_rate',
            'Lagerbestand' => 'stock_quantity',
            'Mindestbestand' => 'min_stock_level',
            'Lagerverfolgung' => 'track_stock',
            'Service' => 'is_service',
            'Status' => 'status',
        ];
        
        $mappedHeaders = [];
        foreach ($header as $index => $col) {
            $col = trim($col);
            if (isset($headerMap[$col])) {
                $mappedHeaders[$index] = $headerMap[$col];
            }
        }
        
        $rowNumber = 1;
        
        DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle, 0, ';')) !== false) {
                $rowNumber++;
                
                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }
                
                $data = [];
                foreach ($mappedHeaders as $index => $field) {
                    $data[$field] = isset($row[$index]) ? trim($row[$index]) : null;
                }
                
                // Skip if no name
                if (empty($data['name'])) {
                    $skipped[] = "Zeile {$rowNumber}: Kein Name angegeben";
                    continue;
                }
                
                // Handle category
                $categoryId = null;
                if (!empty($data['category_name'])) {
                    $category = Category::where('company_id', $companyId)
                        ->where('name', $data['category_name'])
                        ->first();
                    
                    if (!$category) {
                        // Create category if it doesn't exist
                        $category = Category::create([
                            'company_id' => $companyId,
                            'name' => $data['category_name'],
                        ]);
                    }
                    $categoryId = $category->id;
                }
                unset($data['category_name']);
                
                // Convert price strings (German format: 12,34) to float
                if (isset($data['price'])) {
                    $data['price'] = $this->parseGermanNumber($data['price']);
                }
                if (isset($data['cost_price'])) {
                    $data['cost_price'] = $this->parseGermanNumber($data['cost_price']);
                }
                
                // Convert tax rate from percentage to decimal
                if (isset($data['tax_rate'])) {
                    $taxRate = $this->parseGermanNumber($data['tax_rate']);
                    $data['tax_rate'] = $taxRate / 100; // Convert from percentage
                }
                
                // Convert boolean strings
                if (isset($data['track_stock'])) {
                    $data['track_stock'] = in_array(strtolower($data['track_stock']), ['ja', 'yes', '1', 'true']);
                }
                if (isset($data['is_service'])) {
                    $data['is_service'] = in_array(strtolower($data['is_service']), ['ja', 'yes', '1', 'true']);
                }
                
                // Convert numeric fields
                if (isset($data['stock_quantity'])) {
                    $data['stock_quantity'] = (int) $this->parseGermanNumber($data['stock_quantity']);
                }
                if (isset($data['min_stock_level'])) {
                    $data['min_stock_level'] = (int) $this->parseGermanNumber($data['min_stock_level']);
                }
                
                // Validate data
                $validator = Validator::make($data, [
                    'price' => 'nullable|numeric|min:0',
                    'cost_price' => 'nullable|numeric|min:0',
                    'tax_rate' => 'nullable|numeric|min:0|max:1',
                    'stock_quantity' => 'nullable|integer|min:0',
                    'min_stock_level' => 'nullable|integer|min:0',
                    'status' => 'nullable|in:active,inactive',
                ]);
                
                if ($validator->fails()) {
                    $errors[] = "Zeile {$rowNumber}: " . implode(', ', $validator->errors()->all());
                    continue;
                }
                
                // Check if product already exists (by number or SKU)
                $existing = Product::forCompany($companyId)
                    ->where(function ($q) use ($data) {
                        if (!empty($data['number'])) {
                            $q->where('number', $data['number']);
                        }
                        if (!empty($data['sku'])) {
                            $q->orWhere('sku', $data['sku']);
                        }
                    })
                    ->first();
                
                if ($existing) {
                    $skipped[] = "Zeile {$rowNumber}: Produkt existiert bereits ({$data['number']} oder {$data['sku']})";
                    continue;
                }
                
                // Set defaults
                $data['company_id'] = $companyId;
                $data['category_id'] = $categoryId;
                $data['status'] = $data['status'] ?? 'active';
                $data['price'] = $data['price'] ?? 0;
                $data['tax_rate'] = $data['tax_rate'] ?? 0.19; // Default 19% VAT
                $data['track_stock'] = $data['track_stock'] ?? false;
                $data['is_service'] = $data['is_service'] ?? false;
                $data['stock_quantity'] = $data['stock_quantity'] ?? 0;
                $data['min_stock_level'] = $data['min_stock_level'] ?? 0;
                
                // Create product
                $product = Product::create($data);
                $success[] = "Zeile {$rowNumber}: Produkt '{$product->name}' erfolgreich importiert";
            }
            
            DB::commit();
            
            fclose($handle);
            
            $message = count($success) . ' Produkte erfolgreich importiert.';
            if (count($skipped) > 0) {
                $message .= ' ' . count($skipped) . ' Zeilen übersprungen.';
            }
            if (count($errors) > 0) {
                $message .= ' ' . count($errors) . ' Fehler aufgetreten.';
            }
            
            return back()->with('success', $message)
                ->with('import_details', [
                    'success' => $success,
                    'skipped' => $skipped,
                    'errors' => $errors,
                ]);
                
        } catch (\Exception $e) {
            DB::rollBack();
            fclose($handle);
            
            return back()->withErrors(['file' => 'Fehler beim Import: ' . $e->getMessage()]);
        }
    }

    /**
     * Import invoices from CSV
     */
    public function importInvoices(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:10240', // 10MB max
        ]);

        $companyId = $this->getEffectiveCompanyId();
        $file = $request->file('file');
        
        $errors = [];
        $success = [];
        $skipped = [];
        
        $handle = fopen($file->getRealPath(), 'r');
        
        // Skip UTF-8 BOM if present
        $firstLine = fgets($handle);
        if (substr($firstLine, 0, 3) === "\xEF\xBB\xBF") {
            rewind($handle);
            fseek($handle, 3);
        } else {
            rewind($handle);
        }
        
        // Read header row
        $header = fgetcsv($handle, 0, ';');
        if (!$header) {
            return back()->withErrors(['file' => 'Die CSV-Datei ist leer oder ungültig.']);
        }
        
        // Map German headers to English field names
        $headerMap = [
            'Rechnungsnummer' => 'number',
            'Kunde' => 'customer_name',
            'Kundennummer' => 'customer_number',
            'Kunden-E-Mail' => 'customer_email',
            'Rechnungsdatum' => 'issue_date',
            'Fälligkeitsdatum' => 'due_date',
            'Status' => 'status',
            'Zwischensumme' => 'subtotal',
            'Steuerbetrag' => 'tax_amount',
            'Gesamtbetrag' => 'total',
            'Steuersatz' => 'tax_rate',
            'Notizen' => 'notes',
            'Zahlungsmethode' => 'payment_method',
            'Zahlungsbedingungen' => 'payment_terms',
        ];
        
        $mappedHeaders = [];
        foreach ($header as $index => $col) {
            $col = trim($col);
            if (isset($headerMap[$col])) {
                $mappedHeaders[$index] = $headerMap[$col];
            }
        }
        
        $rowNumber = 1;
        $currentUser = $request->user();
        
        DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle, 0, ';')) !== false) {
                $rowNumber++;
                
                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }
                
                $data = [];
                foreach ($mappedHeaders as $index => $field) {
                    $data[$field] = isset($row[$index]) ? trim($row[$index]) : null;
                }
                
                // Skip if no invoice number
                if (empty($data['number'])) {
                    $skipped[] = "Zeile {$rowNumber}: Keine Rechnungsnummer angegeben";
                    continue;
                }
                
                // Check if invoice already exists
                $existing = Invoice::forCompany($companyId)
                    ->where('number', $data['number'])
                    ->first();
                
                if ($existing) {
                    $skipped[] = "Zeile {$rowNumber}: Rechnung '{$data['number']}' existiert bereits";
                    continue;
                }
                
                // Find or create customer
                $customer = null;
                if (!empty($data['customer_number'])) {
                    $customer = Customer::forCompany($companyId)
                        ->where('number', $data['customer_number'])
                        ->first();
                } elseif (!empty($data['customer_email'])) {
                    $customer = Customer::forCompany($companyId)
                        ->where('email', $data['customer_email'])
                        ->first();
                } elseif (!empty($data['customer_name'])) {
                    $customer = Customer::forCompany($companyId)
                        ->where('name', $data['customer_name'])
                        ->first();
                }
                
                if (!$customer) {
                    // Create customer if not found
                    if (empty($data['customer_name'])) {
                        $errors[] = "Zeile {$rowNumber}: Kunde nicht gefunden und kein Name angegeben";
                        continue;
                    }
                    
                    $customer = Customer::create([
                        'company_id' => $companyId,
                        'name' => $data['customer_name'],
                        'email' => $data['customer_email'] ?? null,
                        'status' => 'active',
                    ]);
                }
                
                // Parse dates (German format: dd.mm.yyyy)
                $issueDate = null;
                if (!empty($data['issue_date'])) {
                    try {
                        $issueDate = \Carbon\Carbon::createFromFormat('d.m.Y', $data['issue_date']);
                    } catch (\Exception $e) {
                        try {
                            $issueDate = \Carbon\Carbon::parse($data['issue_date']);
                        } catch (\Exception $e2) {
                            $errors[] = "Zeile {$rowNumber}: Ungültiges Rechnungsdatum: {$data['issue_date']}";
                            continue;
                        }
                    }
                } else {
                    $issueDate = now();
                }
                
                $dueDate = null;
                if (!empty($data['due_date'])) {
                    try {
                        $dueDate = \Carbon\Carbon::createFromFormat('d.m.Y', $data['due_date']);
                    } catch (\Exception $e) {
                        try {
                            $dueDate = \Carbon\Carbon::parse($data['due_date']);
                        } catch (\Exception $e2) {
                            $dueDate = $issueDate->copy()->addDays(14); // Default 14 days
                        }
                    }
                } else {
                    $dueDate = $issueDate->copy()->addDays(14);
                }
                
                // Parse amounts
                $subtotal = $this->parseGermanNumber($data['subtotal'] ?? '0');
                $taxAmount = $this->parseGermanNumber($data['tax_amount'] ?? '0');
                $total = $this->parseGermanNumber($data['total'] ?? '0');
                
                // Parse tax rate (percentage to decimal)
                $taxRate = 0.19; // Default 19%
                if (!empty($data['tax_rate'])) {
                    $taxRateValue = $this->parseGermanNumber($data['tax_rate']);
                    $taxRate = $taxRateValue / 100; // Convert from percentage
                } elseif ($subtotal > 0) {
                    // Calculate tax rate from amounts
                    $taxRate = $taxAmount / $subtotal;
                }
                
                // Validate data
                $validator = Validator::make([
                    'number' => $data['number'],
                    'status' => $data['status'] ?? 'draft',
                    'subtotal' => $subtotal,
                    'total' => $total,
                ], [
                    'number' => 'required|string|max:255',
                    'status' => 'nullable|in:draft,sent,paid,overdue,cancelled',
                    'subtotal' => 'nullable|numeric|min:0',
                    'total' => 'nullable|numeric|min:0',
                ]);
                
                if ($validator->fails()) {
                    $errors[] = "Zeile {$rowNumber}: " . implode(', ', $validator->errors()->all());
                    continue;
                }
                
                // Create invoice
                $invoice = Invoice::create([
                    'company_id' => $companyId,
                    'customer_id' => $customer->id,
                    'user_id' => $currentUser->id,
                    'number' => $data['number'],
                    'issue_date' => $issueDate,
                    'due_date' => $dueDate,
                    'status' => $data['status'] ?? 'draft',
                    'subtotal' => $subtotal,
                    'tax_rate' => $taxRate,
                    'tax_amount' => $taxAmount,
                    'total' => $total,
                    'notes' => $data['notes'] ?? null,
                    'payment_method' => $data['payment_method'] ?? null,
                    'payment_terms' => $data['payment_terms'] ?? null,
                ]);
                
                // If no items, create a single item from the totals
                if ($subtotal > 0) {
                    InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'product_id' => null,
                        'description' => 'Importierte Position',
                        'quantity' => 1,
                        'unit_price' => $subtotal,
                        'unit' => 'Stk.',
                        'tax_rate' => $taxRate,
                        'total' => $subtotal,
                        'sort_order' => 0,
                    ]);
                }
                
                $success[] = "Zeile {$rowNumber}: Rechnung '{$invoice->number}' erfolgreich importiert";
            }
            
            DB::commit();
            
            fclose($handle);
            
            $message = count($success) . ' Rechnungen erfolgreich importiert.';
            if (count($skipped) > 0) {
                $message .= ' ' . count($skipped) . ' Zeilen übersprungen.';
            }
            if (count($errors) > 0) {
                $message .= ' ' . count($errors) . ' Fehler aufgetreten.';
            }
            
            return back()->with('success', $message)
                ->with('import_details', [
                    'success' => $success,
                    'skipped' => $skipped,
                    'errors' => $errors,
                ]);
                
        } catch (\Exception $e) {
            DB::rollBack();
            fclose($handle);
            
            return back()->withErrors(['file' => 'Fehler beim Import: ' . $e->getMessage()]);
        }
    }

    /**
     * Parse German number format (12,34) to float
     */
    private function parseGermanNumber(string $value): float
    {
        if (empty($value)) {
            return 0;
        }
        
        // Remove spaces and convert comma to dot
        $value = str_replace([' ', ','], ['', '.'], $value);
        
        return (float) $value;
    }
}

