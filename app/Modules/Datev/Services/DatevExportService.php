<?php

namespace App\Modules\Datev\Services;

use App\Modules\Invoice\Models\Invoice;
use App\Modules\Customer\Models\Customer;
use App\Modules\Payment\Models\Payment;
use App\Modules\Expense\Models\Expense;
use App\Services\SettingsService;
use Illuminate\Support\Facades\Response as ResponseFacade;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Carbon\Carbon;

class DatevExportService
{
    protected $settingsService;

    public function __construct(SettingsService $settingsService)
    {
        $this->settingsService = $settingsService;
    }

    /**
     * Get DATEV account configuration for a company
     */
    private function getAccountConfig(string $companyId): array
    {
        return [
            'revenue' => $this->settingsService->get('datev_revenue_account', $companyId, '8400'),
            'receivables' => $this->settingsService->get('datev_receivables_account', $companyId, '1200'),
            'bank' => $this->settingsService->get('datev_bank_account', $companyId, '1800'),
            'expenses' => $this->settingsService->get('datev_expenses_account', $companyId, '6000'),
            'vat' => $this->settingsService->get('datev_vat_account', $companyId, '1776'),
            'customer_prefix' => $this->settingsService->get('datev_customer_account_prefix', $companyId, '1000'),
        ];
    }
    /**
     * Export transactions (Umsätze) to DATEV format
     */
    public function exportTransactions(string $companyId, string $dateFrom, string $dateTo, string $format = 'csv'): StreamedResponse
    {
        $invoices = Invoice::forCompany($companyId)
            ->whereBetween('issue_date', [$dateFrom, $dateTo])
            ->where('status', '!=', 'draft')
            ->with(['customer', 'items'])
            ->orderBy('issue_date')
            ->get();

        // Log for debugging
        \Log::info('DATEV Export Transactions', [
            'company_id' => $companyId,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'invoice_count' => $invoices->count(),
        ]);

        $accounts = $this->getAccountConfig($companyId);
        $filename = 'DATEV_Umsaetze_' . date('Y-m-d_His') . '.csv';
        
        return ResponseFacade::stream(function () use ($invoices, $accounts) {
            $file = fopen('php://output', 'w');
            
            // DATEV CSV format header (simplified - only essential fields)
            fputcsv($file, [
                'Umsatz (ohne Soll/Haben-Kennzeichen)',
                'Soll/Haben-Kennzeichen',
                'WKZ Umsatz',
                'Konto',
                'Gegenkonto (ohne BU-Schlüssel)',
                'Belegdatum',
                'Belegfeld 1',
                'Buchungstext',
                'Postensperre',
                'Diverse Adressnummer',
                'Geschäftsbereich',
                'Sachverhalt',
                'Zinssperre',
                'Beleglink',
                'Beleginfo - Art 1',
                'Beleginfo - Inhalt 1',
                'Beleginfo - Art 2',
                'Beleginfo - Inhalt 2',
                'KOST1 - Kostenstelle',
                'KOST2 - Kostenstelle',
                'Kost-Menge',
                'EU-Land u. UStID',
                'EU-Steuersatz',
                'Sachverhalt L+L',
                'Funktionsergänzung L+L',
                'BU 49 Hauptfunktionstyp',
                'BU 49 Hauptfunktionsnummer',
                'BU 49 Funktionsergänzung',
                'Zusatzinformation - Art 1',
                'Zusatzinformation - Inhalt 1',
                'Zusatzinformation - Art 2',
                'Zusatzinformation - Inhalt 2',
                'Zusatzinformation - Art 3',
                'Zusatzinformation - Inhalt 3',
                'Zusatzinformation - Art 4',
                'Zusatzinformation - Inhalt 4',
                'Zusatzinformation - Art 5',
                'Zusatzinformation - Inhalt 5',
                'Zusatzinformation - Art 6',
                'Zusatzinformation - Inhalt 6',
                'Zusatzinformation - Art 7',
                'Zusatzinformation - Inhalt 7',
                'Zusatzinformation - Art 8',
                'Zusatzinformation - Inhalt 8',
                'Zusatzinformation - Art 9',
                'Zusatzinformation - Inhalt 9',
                'Zusatzinformation - Art 10',
                'Zusatzinformation - Inhalt 10',
                'Stück',
                'Gewicht',
                'Zahlweise',
                'Forderungsart',
                'Veranlagungsjahr',
                'Zugeordnete Fälligkeit',
            ], ';');

            if ($invoices->isEmpty()) {
                // If no invoices, still write header but add a note
                \Log::warning('DATEV Export: No invoices found for the selected date range');
            }

            foreach ($invoices as $invoice) {
                if (!$invoice->issue_date || !$invoice->total) {
                    \Log::warning('DATEV Export: Skipping invoice with missing data', [
                        'invoice_id' => $invoice->id,
                        'invoice_number' => $invoice->number,
                    ]);
                    continue;
                }

                $issueDate = Carbon::parse($invoice->issue_date);
                $total = abs($invoice->total ?? 0);
                
                if ($total == 0) {
                    continue; // Skip zero-amount invoices
                }
                
                // S = Soll (debit), H = Haben (credit)
                // For invoices: Soll - we're receiving money
                $sh = 'S';
                
                // DATEV account numbers from configuration
                $account = $accounts['revenue'];
                $counterAccount = $accounts['receivables'];
                
                // Build row with all required fields
                $row = array_fill(0, 52, '');
                $row[0] = number_format($total, 2, ',', '');
                $row[1] = $sh;
                $row[2] = 'EUR';
                $row[3] = $account;
                $row[4] = $counterAccount;
                $row[5] = $issueDate->format('d.m.Y');
                $row[6] = $invoice->number ?? '';
                $row[7] = 'Rechnung ' . ($invoice->number ?? '');
                $row[8] = '0';
                $row[51] = $invoice->due_date ? Carbon::parse($invoice->due_date)->format('d.m.Y') : '';
                
                fputcsv($file, $row, ';');
            }
            
            fclose($file);
        }, 200, [
            'Content-Type' => 'text/csv; charset=ISO-8859-1',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ]);
    }

    /**
     * Export customers (Debitoren) to DATEV format
     */
    public function exportCustomers(string $companyId, string $format = 'csv'): StreamedResponse
    {
        $customers = Customer::forCompany($companyId)
            ->orderBy('name')
            ->get();

        $accounts = $this->getAccountConfig($companyId);
        $filename = 'DATEV_Debitoren_' . date('Y-m-d_His') . '.csv';
        
        return ResponseFacade::stream(function () use ($customers, $accounts) {
            $file = fopen('php://output', 'w');
            
            // DATEV Debitoren format (simplified)
            fputcsv($file, [
                'Konto',
                'Name (Adressatentyp Unternehmen)',
                'Name (Adressatentyp natürliche Person)',
                'Adressatentyp',
                'Kurzbezeichnung',
                'EU-Land',
                'EU-USt-IdNr.',
                'Straße',
                'Postleitzahl',
                'Ort',
                'Land',
                'Telefon',
                'E-Mail',
                'Bankleitzahl',
                'Kontonummer',
                'IBAN',
                'BIC',
                'Adressnummer',
            ], ';');

            foreach ($customers as $index => $customer) {
                $accountNumber = $accounts['customer_prefix'] . str_pad($index + 1, 4, '0', STR_PAD_LEFT);
                $addressType = $customer->type === 'business' ? 'F' : 'P';
                
                fputcsv($file, [
                    $accountNumber,
                    $customer->type === 'business' ? $customer->name : '',
                    $customer->type === 'private' ? $customer->name : '',
                    $addressType,
                    $customer->name,
                    'DE',
                    $customer->vat_id ?? '',
                    $customer->address ?? '',
                    $customer->postal_code ?? '',
                    $customer->city ?? '',
                    $customer->country ?? 'Deutschland',
                    $customer->phone ?? '',
                    $customer->email ?? '',
                    '',
                    '',
                    '',
                    '',
                    $customer->number ?? '',
                ], ';');
            }
            
            fclose($file);
        }, 200, [
            'Content-Type' => 'text/csv; charset=ISO-8859-1',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ]);
    }

    /**
     * Export payments (Zahlungen) to DATEV format
     */
    public function exportPayments(string $companyId, string $dateFrom, string $dateTo, string $format = 'csv'): StreamedResponse
    {
        $payments = Payment::where('company_id', $companyId)
            ->whereBetween('payment_date', [$dateFrom, $dateTo])
            ->with(['invoice.customer'])
            ->orderBy('payment_date')
            ->get();

        $accounts = $this->getAccountConfig($companyId);
        $filename = 'DATEV_Zahlungen_' . date('Y-m-d_His') . '.csv';
        
        return ResponseFacade::stream(function () use ($payments, $accounts) {
            $file = fopen('php://output', 'w');
            
            // Same header as transactions
            fputcsv($file, [
                'Umsatz (ohne Soll/Haben-Kennzeichen)',
                'Soll/Haben-Kennzeichen',
                'WKZ Umsatz',
                'Konto',
                'Gegenkonto (ohne BU-Schlüssel)',
                'Belegdatum',
                'Belegfeld 1',
                'Buchungstext',
                'Postensperre',
                'Diverse Adressnummer',
                'Geschäftsbereich',
                'Sachverhalt',
                'Zinssperre',
                'Beleglink',
                'Beleginfo - Art 1',
                'Beleginfo - Inhalt 1',
                'Beleginfo - Art 2',
                'Beleginfo - Inhalt 2',
                'KOST1 - Kostenstelle',
                'KOST2 - Kostenstelle',
                'Kost-Menge',
                'EU-Land u. UStID',
                'EU-Steuersatz',
                'Sachverhalt L+L',
                'Funktionsergänzung L+L',
                'BU 49 Hauptfunktionstyp',
                'BU 49 Hauptfunktionsnummer',
                'BU 49 Funktionsergänzung',
                'Zusatzinformation - Art 1',
                'Zusatzinformation - Inhalt 1',
                'Zusatzinformation - Art 2',
                'Zusatzinformation - Inhalt 2',
                'Zusatzinformation - Art 3',
                'Zusatzinformation - Inhalt 3',
                'Zusatzinformation - Art 4',
                'Zusatzinformation - Inhalt 4',
                'Zusatzinformation - Art 5',
                'Zusatzinformation - Inhalt 5',
                'Zusatzinformation - Art 6',
                'Zusatzinformation - Inhalt 6',
                'Zusatzinformation - Art 7',
                'Zusatzinformation - Inhalt 7',
                'Zusatzinformation - Art 8',
                'Zusatzinformation - Inhalt 8',
                'Zusatzinformation - Art 9',
                'Zusatzinformation - Inhalt 9',
                'Zusatzinformation - Art 10',
                'Zusatzinformation - Inhalt 10',
                'Stück',
                'Gewicht',
                'Zahlweise',
                'Forderungsart',
                'Veranlagungsjahr',
                'Zugeordnete Fälligkeit',
            ], ';');

            foreach ($payments as $payment) {
                $paymentDate = Carbon::parse($payment->payment_date);
                $amount = abs($payment->amount);
                
                // For payments: Haben (credit) - money is coming in
                $sh = 'H';
                
                $account = $accounts['bank'];
                $counterAccount = $accounts['receivables'];
                
                $row = array_fill(0, 52, '');
                $row[0] = number_format($amount, 2, ',', '');
                $row[1] = $sh;
                $row[2] = 'EUR';
                $row[3] = $account;
                $row[4] = $counterAccount;
                $row[5] = $paymentDate->format('d.m.Y');
                $row[6] = $payment->invoice->number ?? '';
                $row[7] = 'Zahlung ' . ($payment->invoice->number ?? '');
                
                fputcsv($file, $row, ';');
            }
            
            fclose($file);
        }, 200, [
            'Content-Type' => 'text/csv; charset=ISO-8859-1',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ]);
    }

    /**
     * Export expenses (Ausgaben) to DATEV format
     */
    public function exportExpenses(string $companyId, string $dateFrom, string $dateTo, string $format = 'csv'): StreamedResponse
    {
        $expenses = Expense::where('company_id', $companyId)
            ->whereBetween('expense_date', [$dateFrom, $dateTo])
            ->orderBy('expense_date')
            ->get();

        $accounts = $this->getAccountConfig($companyId);
        $filename = 'DATEV_Ausgaben_' . date('Y-m-d_His') . '.csv';
        
        return ResponseFacade::stream(function () use ($expenses, $accounts) {
            $file = fopen('php://output', 'w');
            
            // Same header as transactions
            fputcsv($file, [
                'Umsatz (ohne Soll/Haben-Kennzeichen)',
                'Soll/Haben-Kennzeichen',
                'WKZ Umsatz',
                'Konto',
                'Gegenkonto (ohne BU-Schlüssel)',
                'Belegdatum',
                'Belegfeld 1',
                'Buchungstext',
                'Postensperre',
                'Diverse Adressnummer',
                'Geschäftsbereich',
                'Sachverhalt',
                'Zinssperre',
                'Beleglink',
                'Beleginfo - Art 1',
                'Beleginfo - Inhalt 1',
                'Beleginfo - Art 2',
                'Beleginfo - Inhalt 2',
                'KOST1 - Kostenstelle',
                'KOST2 - Kostenstelle',
                'Kost-Menge',
                'EU-Land u. UStID',
                'EU-Steuersatz',
                'Sachverhalt L+L',
                'Funktionsergänzung L+L',
                'BU 49 Hauptfunktionstyp',
                'BU 49 Hauptfunktionsnummer',
                'BU 49 Funktionsergänzung',
                'Zusatzinformation - Art 1',
                'Zusatzinformation - Inhalt 1',
                'Zusatzinformation - Art 2',
                'Zusatzinformation - Inhalt 2',
                'Zusatzinformation - Art 3',
                'Zusatzinformation - Inhalt 3',
                'Zusatzinformation - Art 4',
                'Zusatzinformation - Inhalt 4',
                'Zusatzinformation - Art 5',
                'Zusatzinformation - Inhalt 5',
                'Zusatzinformation - Art 6',
                'Zusatzinformation - Inhalt 6',
                'Zusatzinformation - Art 7',
                'Zusatzinformation - Inhalt 7',
                'Zusatzinformation - Art 8',
                'Zusatzinformation - Inhalt 8',
                'Zusatzinformation - Art 9',
                'Zusatzinformation - Inhalt 9',
                'Zusatzinformation - Art 10',
                'Zusatzinformation - Inhalt 10',
                'Stück',
                'Gewicht',
                'Zahlweise',
                'Forderungsart',
                'Veranlagungsjahr',
                'Zugeordnete Fälligkeit',
            ], ';');

            foreach ($expenses as $expense) {
                $expenseDate = Carbon::parse($expense->expense_date);
                $amount = abs($expense->amount);
                
                // For expenses: Soll (debit) - money going out
                $sh = 'S';
                
                $account = $accounts['expenses'];
                $counterAccount = $accounts['bank'];
                
                $row = array_fill(0, 52, '');
                $row[0] = number_format($amount, 2, ',', '');
                $row[1] = $sh;
                $row[2] = 'EUR';
                $row[3] = $account;
                $row[4] = $counterAccount;
                $row[5] = $expenseDate->format('d.m.Y');
                $row[6] = $expense->title ?? '';
                $row[7] = 'Ausgabe: ' . ($expense->title ?? '');
                
                fputcsv($file, $row, ';');
            }
            
            fclose($file);
        }, 200, [
            'Content-Type' => 'text/csv; charset=ISO-8859-1',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ]);
    }

    /**
     * Export VAT report (Umsatzsteuer) to DATEV format
     */
    public function exportVat(string $companyId, string $dateFrom, string $dateTo, string $format = 'csv'): StreamedResponse
    {
        $invoices = Invoice::forCompany($companyId)
            ->whereBetween('issue_date', [$dateFrom, $dateTo])
            ->where('status', '!=', 'draft')
            ->with(['items'])
            ->orderBy('issue_date')
            ->get();

        $accounts = $this->getAccountConfig($companyId);
        $filename = 'DATEV_Umsatzsteuer_' . date('Y-m-d_His') . '.csv';
        
        return ResponseFacade::stream(function () use ($invoices, $accounts) {
            $file = fopen('php://output', 'w');
            
            // DATEV VAT format
            fputcsv($file, [
                'Umsatz (ohne Soll/Haben-Kennzeichen)',
                'Soll/Haben-Kennzeichen',
                'WKZ Umsatz',
                'Konto',
                'Gegenkonto (ohne BU-Schlüssel)',
                'Belegdatum',
                'Belegfeld 1',
                'Buchungstext',
                'Postensperre',
                'Diverse Adressnummer',
                'Geschäftsbereich',
                'Sachverhalt',
                'Zinssperre',
                'Beleglink',
                'Beleginfo - Art 1',
                'Beleginfo - Inhalt 1',
                'Beleginfo - Art 2',
                'Beleginfo - Inhalt 2',
                'KOST1 - Kostenstelle',
                'KOST2 - Kostenstelle',
                'Kost-Menge',
                'EU-Land u. UStID',
                'EU-Steuersatz',
                'Sachverhalt L+L',
                'Funktionsergänzung L+L',
                'BU 49 Hauptfunktionstyp',
                'BU 49 Hauptfunktionsnummer',
                'BU 49 Funktionsergänzung',
                'Zusatzinformation - Art 1',
                'Zusatzinformation - Inhalt 1',
                'Zusatzinformation - Art 2',
                'Zusatzinformation - Inhalt 2',
                'Zusatzinformation - Art 3',
                'Zusatzinformation - Inhalt 3',
                'Zusatzinformation - Art 4',
                'Zusatzinformation - Inhalt 4',
                'Zusatzinformation - Art 5',
                'Zusatzinformation - Inhalt 5',
                'Zusatzinformation - Art 6',
                'Zusatzinformation - Inhalt 6',
                'Zusatzinformation - Art 7',
                'Zusatzinformation - Inhalt 7',
                'Zusatzinformation - Art 8',
                'Zusatzinformation - Inhalt 8',
                'Zusatzinformation - Art 9',
                'Zusatzinformation - Inhalt 9',
                'Zusatzinformation - Art 10',
                'Zusatzinformation - Inhalt 10',
                'Stück',
                'Gewicht',
                'Zahlweise',
                'Forderungsart',
                'Veranlagungsjahr',
                'Zugeordnete Fälligkeit',
            ], ';');

            foreach ($invoices as $invoice) {
                $issueDate = Carbon::parse($invoice->issue_date);
                $taxAmount = abs($invoice->tax_amount);
                
                if ($taxAmount > 0) {
                    $sh = 'S';
                    $account = $accounts['vat'];
                    $counterAccount = $accounts['revenue'];
                    
                    $row = array_fill(0, 52, '');
                    $row[0] = number_format($taxAmount, 2, ',', '');
                    $row[1] = $sh;
                    $row[2] = 'EUR';
                    $row[3] = $account;
                    $row[4] = $counterAccount;
                    $row[5] = $issueDate->format('d.m.Y');
                    $row[6] = $invoice->number;
                    $row[7] = 'Umsatzsteuer ' . $invoice->number;
                    
                    fputcsv($file, $row, ';');
                }
            }
            
            fclose($file);
        }, 200, [
            'Content-Type' => 'text/csv; charset=ISO-8859-1',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ]);
    }
}

