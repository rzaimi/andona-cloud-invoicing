<?php

namespace App\Modules\Datev\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Datev\Services\DatevExportService;
use App\Services\ContextService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DatevController extends Controller
{
    protected $datevService;

    public function __construct(ContextService $contextService, DatevExportService $datevService)
    {
        parent::__construct($contextService);
        $this->middleware('auth');
        $this->datevService = $datevService;
    }

    /**
     * Show DATEV export page
     */
    public function index(Request $request)
    {
        $companyId = $this->getEffectiveCompanyId();

        return Inertia::render('datev/index', [
            'company_id' => $companyId,
        ]);
    }

    /**
     * Export invoices/transactions (Umsätze) to DATEV format
     */
    public function exportTransactions(Request $request)
    {
        try {
            $companyId = $this->getEffectiveCompanyId();
            
            $validated = $request->validate([
                'date_from' => 'required|date',
                'date_to' => 'required|date|after_or_equal:date_from',
                'format' => 'nullable|string|in:csv,xml',
            ]);

            return $this->datevService->exportTransactions(
                $companyId,
                $validated['date_from'],
                $validated['date_to'],
                $validated['format'] ?? 'csv'
            );
        } catch (\Exception $e) {
            \Log::error('DATEV Export Error (Transactions): ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'message' => 'Fehler beim Exportieren: ' . $e->getMessage(),
                'errors' => $e instanceof \Illuminate\Validation\ValidationException ? $e->errors() : null,
            ], 500);
        }
    }

    /**
     * Export customers (Debitoren) to DATEV format
     */
    public function exportCustomers(Request $request)
    {
        try {
            $companyId = $this->getEffectiveCompanyId();
            
            $validated = $request->validate([
                'format' => 'nullable|string|in:csv,xml',
            ]);

            return $this->datevService->exportCustomers(
                $companyId,
                $validated['format'] ?? 'csv'
            );
        } catch (\Exception $e) {
            \Log::error('DATEV Export Error (Customers): ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'message' => 'Fehler beim Exportieren: ' . $e->getMessage(),
                'errors' => $e instanceof \Illuminate\Validation\ValidationException ? $e->errors() : null,
            ], 500);
        }
    }

    /**
     * Export payments (Zahlungen) to DATEV format
     */
    public function exportPayments(Request $request)
    {
        try {
            $companyId = $this->getEffectiveCompanyId();
            
            $validated = $request->validate([
                'date_from' => 'required|date',
                'date_to' => 'required|date|after_or_equal:date_from',
                'format' => 'nullable|string|in:csv,xml',
            ]);

            return $this->datevService->exportPayments(
                $companyId,
                $validated['date_from'],
                $validated['date_to'],
                $validated['format'] ?? 'csv'
            );
        } catch (\Exception $e) {
            \Log::error('DATEV Export Error (Payments): ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'message' => 'Fehler beim Exportieren: ' . $e->getMessage(),
                'errors' => $e instanceof \Illuminate\Validation\ValidationException ? $e->errors() : null,
            ], 500);
        }
    }

    /**
     * Export expenses (Ausgaben) to DATEV format
     */
    public function exportExpenses(Request $request)
    {
        try {
            $companyId = $this->getEffectiveCompanyId();
            
            $validated = $request->validate([
                'date_from' => 'required|date',
                'date_to' => 'required|date|after_or_equal:date_from',
                'format' => 'nullable|string|in:csv,xml',
            ]);

            return $this->datevService->exportExpenses(
                $companyId,
                $validated['date_from'],
                $validated['date_to'],
                $validated['format'] ?? 'csv'
            );
        } catch (\Exception $e) {
            \Log::error('DATEV Export Error (Expenses): ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'message' => 'Fehler beim Exportieren: ' . $e->getMessage(),
                'errors' => $e instanceof \Illuminate\Validation\ValidationException ? $e->errors() : null,
            ], 500);
        }
    }

    /**
     * Export VAT report (Umsatzsteuer) to DATEV format
     */
    public function exportVat(Request $request)
    {
        try {
            $companyId = $this->getEffectiveCompanyId();
            
            $validated = $request->validate([
                'date_from' => 'required|date',
                'date_to' => 'required|date|after_or_equal:date_from',
                'format' => 'nullable|string|in:csv,xml',
            ]);

            return $this->datevService->exportVat(
                $companyId,
                $validated['date_from'],
                $validated['date_to'],
                $validated['format'] ?? 'csv'
            );
        } catch (\Exception $e) {
            \Log::error('DATEV Export Error (VAT): ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'message' => 'Fehler beim Exportieren: ' . $e->getMessage(),
                'errors' => $e instanceof \Illuminate\Validation\ValidationException ? $e->errors() : null,
            ], 500);
        }
    }
}

