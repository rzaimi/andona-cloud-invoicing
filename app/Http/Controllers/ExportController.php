<?php

namespace App\Http\Controllers;

use App\Modules\Customer\Models\Customer;
use App\Modules\Product\Models\Product;
use App\Modules\Invoice\Models\Invoice;
use App\Modules\Offer\Models\Offer;
use App\Services\ContextService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function __construct(ContextService $contextService)
    {
        parent::__construct($contextService);
        $this->middleware('auth');
    }

    /**
     * Export customers to CSV
     */
    public function exportCustomers(Request $request): StreamedResponse
    {
        $companyId = $this->getEffectiveCompanyId();
        
        $query = Customer::forCompany($companyId);
        
        // Apply filters if provided
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('number', 'like', "%{$search}%");
            });
        }
        
        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }
        
        $customers = $query->orderBy('created_at', 'desc')->get();
        
        $filename = 'kunden_export_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];
        
        return Response::stream(function () use ($customers) {
            $file = fopen('php://output', 'w');
            
            // Add UTF-8 BOM for Excel compatibility
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Headers
            fputcsv($file, [
                'Nummer',
                'Name',
                'E-Mail',
                'Telefon',
                'Adresse',
                'PLZ',
                'Stadt',
                'Land',
                'Steuernummer',
                'USt-IdNr.',
                'Ansprechpartner',
                'Typ',
                'Status',
                'Erstellt am',
            ], ';');
            
            // Data rows
            foreach ($customers as $customer) {
                fputcsv($file, [
                    $customer->number ?? '',
                    $customer->name ?? '',
                    $customer->email ?? '',
                    $customer->phone ?? '',
                    $customer->address ?? '',
                    $customer->postal_code ?? '',
                    $customer->city ?? '',
                    $customer->country ?? '',
                    $customer->tax_number ?? '',
                    $customer->vat_number ?? '',
                    $customer->contact_person ?? '',
                    $customer->customer_type ?? '',
                    $customer->status ?? '',
                    $customer->created_at?->format('Y-m-d H:i:s') ?? '',
                ], ';');
            }
            
            fclose($file);
        }, 200, $headers);
    }

    /**
     * Export products to CSV
     */
    public function exportProducts(Request $request): StreamedResponse
    {
        $companyId = $this->getEffectiveCompanyId();
        
        $query = Product::forCompany($companyId);
        
        // Apply filters if provided
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('number', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }
        
        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }
        
        if ($request->filled('category')) {
            $query->byCategory($request->get('category'));
        }
        
        $products = $query->with('category')->orderBy('created_at', 'desc')->get();
        
        $filename = 'produkte_export_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];
        
        return Response::stream(function () use ($products) {
            $file = fopen('php://output', 'w');
            
            // Add UTF-8 BOM for Excel compatibility
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Headers
            fputcsv($file, [
                'Nummer',
                'Name',
                'Beschreibung',
                'Kategorie',
                'Artikelnummer (SKU)',
                'Barcode',
                'Einheit',
                'Preis',
                'Einkaufspreis',
                'Steuersatz (%)',
                'Lagerbestand',
                'Mindestbestand',
                'Lagerverfolgung',
                'Service',
                'Status',
                'Erstellt am',
            ], ';');
            
            // Data rows
            foreach ($products as $product) {
                fputcsv($file, [
                    $product->number ?? '',
                    $product->name ?? '',
                    $product->description ?? '',
                    $product->category?->name ?? '',
                    $product->sku ?? '',
                    $product->barcode ?? '',
                    $product->unit ?? '',
                    number_format($product->price ?? 0, 2, ',', '.'),
                    number_format($product->cost_price ?? 0, 2, ',', '.'),
                    number_format(($product->tax_rate ?? 0) * 100, 2, ',', '.'),
                    $product->stock_quantity ?? 0,
                    $product->min_stock_level ?? 0,
                    $product->track_stock ? 'Ja' : 'Nein',
                    $product->is_service ? 'Ja' : 'Nein',
                    $product->status ?? '',
                    $product->created_at?->format('Y-m-d H:i:s') ?? '',
                ], ';');
            }
            
            fclose($file);
        }, 200, $headers);
    }

    /**
     * Export invoices to CSV
     */
    public function exportInvoices(Request $request): StreamedResponse
    {
        $companyId = $this->getEffectiveCompanyId();
        
        $query = Invoice::forCompany($companyId)->with(['customer', 'user']);
        
        // Apply filters if provided
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('number', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
            });
        }
        
        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }
        
        if ($request->filled('date_from')) {
            $query->whereDate('issue_date', '>=', $request->get('date_from'));
        }
        
        if ($request->filled('date_to')) {
            $query->whereDate('issue_date', '<=', $request->get('date_to'));
        }
        
        $invoices = $query->orderBy('created_at', 'desc')->get();
        
        $filename = 'rechnungen_export_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];
        
        return Response::stream(function () use ($invoices) {
            $file = fopen('php://output', 'w');
            
            // Add UTF-8 BOM for Excel compatibility
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Headers
            fputcsv($file, [
                'Rechnungsnummer',
                'Kunde',
                'Kundennummer',
                'Rechnungsdatum',
                'Fälligkeitsdatum',
                'Status',
                'Zwischensumme',
                'Steuerbetrag',
                'Gesamtbetrag',
                'Erstellt von',
                'Erstellt am',
            ], ';');
            
            // Data rows
            foreach ($invoices as $invoice) {
                fputcsv($file, [
                    $invoice->number ?? '',
                    $invoice->customer?->name ?? '',
                    $invoice->customer?->number ?? '',
                    $invoice->issue_date ? \Carbon\Carbon::parse($invoice->issue_date)->format('d.m.Y') : '',
                    $invoice->due_date ? \Carbon\Carbon::parse($invoice->due_date)->format('d.m.Y') : '',
                    $invoice->status ?? '',
                    number_format($invoice->subtotal ?? 0, 2, ',', '.'),
                    number_format($invoice->tax_amount ?? 0, 2, ',', '.'),
                    number_format($invoice->total ?? 0, 2, ',', '.'),
                    $invoice->user?->name ?? '',
                    $invoice->created_at?->format('Y-m-d H:i:s') ?? '',
                ], ';');
            }
            
            fclose($file);
        }, 200, $headers);
    }

    /**
     * Export offers to CSV
     */
    public function exportOffers(Request $request): StreamedResponse
    {
        $companyId = $this->getEffectiveCompanyId();
        
        $query = Offer::forCompany($companyId)->with(['customer', 'user']);
        
        // Apply filters if provided
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('number', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
            });
        }
        
        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }
        
        if ($request->filled('date_from')) {
            $query->whereDate('issue_date', '>=', $request->get('date_from'));
        }
        
        if ($request->filled('date_to')) {
            $query->whereDate('issue_date', '<=', $request->get('date_to'));
        }
        
        $offers = $query->orderBy('created_at', 'desc')->get();
        
        $filename = 'angebote_export_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];
        
        return Response::stream(function () use ($offers) {
            $file = fopen('php://output', 'w');
            
            // Add UTF-8 BOM for Excel compatibility
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Headers
            fputcsv($file, [
                'Angebotsnummer',
                'Kunde',
                'Kundennummer',
                'Angebotsdatum',
                'Gültig bis',
                'Status',
                'Zwischensumme',
                'Steuerbetrag',
                'Gesamtbetrag',
                'Erstellt von',
                'Erstellt am',
            ], ';');
            
            // Data rows
            foreach ($offers as $offer) {
                fputcsv($file, [
                    $offer->number ?? '',
                    $offer->customer?->name ?? '',
                    $offer->customer?->number ?? '',
                    $offer->issue_date ? \Carbon\Carbon::parse($offer->issue_date)->format('d.m.Y') : '',
                    $offer->valid_until ? \Carbon\Carbon::parse($offer->valid_until)->format('d.m.Y') : '',
                    $offer->status ?? '',
                    number_format($offer->subtotal ?? 0, 2, ',', '.'),
                    number_format($offer->tax_amount ?? 0, 2, ',', '.'),
                    number_format($offer->total ?? 0, 2, ',', '.'),
                    $offer->user?->name ?? '',
                    $offer->created_at?->format('Y-m-d H:i:s') ?? '',
                ], ';');
            }
            
            fclose($file);
        }, 200, $headers);
    }
}

