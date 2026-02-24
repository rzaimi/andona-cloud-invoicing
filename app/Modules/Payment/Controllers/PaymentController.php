<?php

namespace App\Modules\Payment\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Invoice\Models\Invoice;
use App\Modules\Invoice\Models\InvoiceAuditLog;
use App\Modules\Payment\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $companyId = $this->getEffectiveCompanyId();
        
        $query = Payment::forCompany($companyId)
            ->with(['invoice:id,number,customer_id', 'invoice.customer:id,name', 'createdBy:id,name']);
        
        // Apply invoice filter
        if ($request->invoice_id) {
            $query->where('invoice_id', $request->invoice_id);
        }
        
        // Apply status filter
        if ($request->status && $request->status !== 'all') {
            $query->where('status', $request->status);
        }
        
        // Apply search filter
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('reference', 'like', "%{$request->search}%")
                    ->orWhere('notes', 'like', "%{$request->search}%")
                    ->orWhereHas('invoice', function ($invoiceQuery) use ($request) {
                        $invoiceQuery->where('number', 'like', "%{$request->search}%");
                    });
            });
        }
        
        $payments = $query->latest('payment_date')->paginate(15)->withQueryString();
        
        // Calculate statistics
        $stats = [
            'total' => Payment::forCompany($companyId)->count(),
            'completed' => Payment::forCompany($companyId)->completed()->count(),
            'pending' => Payment::forCompany($companyId)->pending()->count(),
            'total_amount' => Payment::forCompany($companyId)->completed()->sum('amount'),
        ];
        
        return Inertia::render('payments/index', [
            'payments' => $payments,
            'filters' => $request->only(['invoice_id', 'status', 'search']),
            'stats' => $stats,
        ]);
    }
    
    public function create(Request $request)
    {
        $companyId = $this->getEffectiveCompanyId();
        
        $invoices = Invoice::forCompany($companyId)
            ->whereNotIn('status', ['cancelled'])
            ->with(['customer:id,name', 'payments' => function($query) {
                $query->where('status', 'completed');
            }])
            ->select('id', 'number', 'customer_id', 'total', 'status')
            ->orderBy('number', 'desc')
            ->get();
        
        // If invoice_id is provided, pre-select that invoice.
        // Also ensure it is included in the dropdown list even if somehow excluded above.
        $selectedInvoice = null;
        if ($request->invoice_id) {
            $selectedInvoice = Invoice::forCompany($companyId)
                ->with(['customer:id,name', 'payments'])
                ->find($request->invoice_id);
            
            if ($selectedInvoice && !$invoices->contains('id', $selectedInvoice->id)) {
                $invoices->prepend($selectedInvoice->load(['customer:id,name', 'payments' => function($q) {
                    $q->where('status', 'completed');
                }]));
            }
        }
        
        return Inertia::render('payments/create', [
            'invoices' => $invoices,
            'selectedInvoice' => $selectedInvoice,
        ]);
    }
    
    public function store(Request $request)
    {
        $validated = $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'payment_method' => 'nullable|string|max:255',
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'status' => 'nullable|in:pending,completed,cancelled',
        ]);
        
        DB::transaction(function () use ($validated, $request) {
            $user = $request->user();
            $companyId = $this->getEffectiveCompanyId();
            
            // Verify invoice belongs to company
            $invoice = Invoice::forCompany($companyId)->findOrFail($validated['invoice_id']);
            
            // Create payment
            $payment = Payment::create([
                'invoice_id' => $validated['invoice_id'],
                'company_id' => $companyId,
                'amount' => $validated['amount'],
                'payment_date' => $validated['payment_date'],
                'payment_method' => $validated['payment_method'] ?? null,
                'reference' => $validated['reference'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'status' => $validated['status'] ?? 'completed',
                'created_by' => $user->id,
            ]);
            
            // Update invoice status if fully paid
            $totalPaid = $invoice->payments()->where('status', 'completed')->sum('amount');
            if ($totalPaid >= $invoice->total && $invoice->status !== 'paid') {
                $oldStatus = $invoice->status;
                $invoice->status = 'paid';
                $invoice->save();

                // Audit Log: Invoice automatically marked as paid
                InvoiceAuditLog::log(
                    $invoice->id,
                    'paid',
                    $oldStatus,
                    'paid',
                    ['payment_id' => $payment->id, 'amount' => $payment->amount, 'total_paid' => $totalPaid],
                    'Invoice automatically marked as paid after receiving payment of ' . number_format($payment->amount, 2) . ' EUR'
                );
            }
        });
        
        return redirect()->route('payments.index')
            ->with('success', 'Zahlung wurde erfolgreich erfasst.');
    }
    
    public function show(Payment $payment)
    {
        $this->authorize('view', $payment);
        
        $payment->load(['invoice.customer', 'createdBy']);
        
        return Inertia::render('payments/show', [
            'payment' => $payment,
        ]);
    }
    
    public function edit(Payment $payment)
    {
        $this->authorize('update', $payment);
        
        $companyId = $this->getEffectiveCompanyId();
        
        $invoices = Invoice::forCompany($companyId)
            ->whereNotIn('status', ['cancelled'])
            ->with(['customer:id,name', 'payments' => function($query) {
                $query->where('status', 'completed');
            }])
            ->select('id', 'number', 'customer_id', 'total', 'status')
            ->orderBy('number', 'desc')
            ->get();
        
        $payment->load(['invoice']);
        
        // Always include the payment's own invoice in the list
        if ($payment->invoice && !$invoices->contains('id', $payment->invoice_id)) {
            $invoices->prepend($payment->invoice);
        }
        
        return Inertia::render('payments/edit', [
            'payment' => $payment,
            'invoices' => $invoices,
        ]);
    }
    
    public function update(Request $request, Payment $payment)
    {
        $this->authorize('update', $payment);
        
        $validated = $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'payment_method' => 'nullable|string|max:255',
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'status' => 'required|in:pending,completed,cancelled',
        ]);
        
        DB::transaction(function () use ($validated, $payment) {
            $companyId = $this->getEffectiveCompanyId();
            
            // Verify invoice belongs to company
            $invoice = Invoice::forCompany($companyId)->findOrFail($validated['invoice_id']);
            
            $oldInvoiceId = $payment->invoice_id;
            $oldStatus = $payment->status;
            
            // Update payment
            $payment->update([
                'invoice_id' => $validated['invoice_id'],
                'amount' => $validated['amount'],
                'payment_date' => $validated['payment_date'],
                'payment_method' => $validated['payment_method'] ?? null,
                'reference' => $validated['reference'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'status' => $validated['status'],
            ]);
            
            // Update old invoice status if needed
            if ($oldInvoiceId !== $validated['invoice_id']) {
                $oldInvoice = Invoice::forCompany($companyId)->find($oldInvoiceId);
                if ($oldInvoice) {
                    $oldInvoiceTotalPaid = $oldInvoice->payments()->where('status', 'completed')->sum('amount');
                    if ($oldInvoiceTotalPaid < $oldInvoice->total && $oldInvoice->status === 'paid') {
                        $oldInvoice->status = 'sent';
                        $oldInvoice->save();
                    }
                }
            }
            
            // Update new invoice status
            $totalPaid = $invoice->payments()->where('status', 'completed')->sum('amount');
            if ($totalPaid >= $invoice->total && $invoice->status !== 'paid') {
                $invoice->status = 'paid';
                $invoice->save();
            } elseif ($totalPaid < $invoice->total && $invoice->status === 'paid') {
                $invoice->status = 'sent';
                $invoice->save();
            }
        });
        
        return redirect()->route('payments.index')
            ->with('success', 'Zahlung wurde erfolgreich aktualisiert.');
    }
    
    public function destroy(Payment $payment)
    {
        $this->authorize('delete', $payment);
        
        DB::transaction(function () use ($payment) {
            $invoice = $payment->invoice;
            
            $payment->delete();
            
            // Update invoice status if needed
            if ($invoice) {
                $totalPaid = $invoice->payments()->where('status', 'completed')->sum('amount');
                if ($totalPaid < $invoice->total && $invoice->status === 'paid') {
                    $invoice->status = 'sent';
                    $invoice->save();
                }
            }
        });
        
        return redirect()->route('payments.index')
            ->with('success', 'Zahlung wurde erfolgreich gelöscht.');
    }
}
