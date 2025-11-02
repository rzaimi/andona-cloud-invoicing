<?php

namespace App\Modules\Calendar\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Invoice\Models\Invoice;
use App\Modules\Offer\Models\Offer;
use App\Services\ContextService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CalendarController extends Controller
{
    public function __construct(ContextService $contextService)
    {
        parent::__construct($contextService);
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $companyId = $this->getEffectiveCompanyId();

        // Get calendar events
        $events = $this->getCalendarEvents($companyId);

        return Inertia::render('calendar/index', [
            'user' => $this->contextService->getUserContext(),
            'stats' => $this->contextService->getDashboardStats(),
            'events' => $events,
        ]);
    }

    private function getCalendarEvents($companyId)
    {
        $events = collect();

        // Invoice due dates (include overdue and upcoming)
        $invoices = Invoice::where('company_id', $companyId)
            ->where('status', '!=', 'paid')
            ->with('customer')
            ->get();

        foreach ($invoices as $invoice) {
            // Only add if due_date is set
            if ($invoice->due_date) {
                $events->push([
                    'id' => 'invoice_' . $invoice->id,
                    'title' => "Rechnung {$invoice->number} fällig",
                    'type' => 'invoice_due',
                    'date' => $invoice->due_date->format('Y-m-d'),
                    'time' => '09:00',
                    'customer' => $invoice->customer->name ?? 'Unbekannt',
                    'amount' => $invoice->total ?? 0,
                    'status' => $invoice->due_date->isPast() ? 'overdue' : 'pending',
                    'description' => 'Zahlungserinnerung senden',
                    'invoice_id' => $invoice->id,
                ]);
            }
        }

        // Offer expiry dates (include expired and upcoming)
        $offers = Offer::where('company_id', $companyId)
            ->where('status', 'sent')
            ->with('customer')
            ->get();

        foreach ($offers as $offer) {
            // Only add if valid_until is set
            if ($offer->valid_until) {
                $daysUntilExpiry = now()->diffInDays($offer->valid_until, false);
                $events->push([
                    'id' => 'offer_' . $offer->id,
                    'title' => "Angebot {$offer->number} läuft ab",
                    'type' => 'offer_expiry',
                    'date' => $offer->valid_until->format('Y-m-d'),
                    'time' => '23:59',
                    'customer' => $offer->customer->name ?? 'Unbekannt',
                    'amount' => $offer->total ?? 0,
                    'status' => $daysUntilExpiry <= 3 && $daysUntilExpiry >= 0 ? 'expiring' : ($daysUntilExpiry < 0 ? 'expired' : 'active'),
                    'description' => 'Angebot verlängern oder nachfassen',
                    'offer_id' => $offer->id,
                ]);
            }
        }

        // Add recurring events (monthly reports, etc.)
        $events->push([
            'id' => 'monthly_report_' . now()->format('Y_m'),
            'title' => 'Monatsbericht erstellen',
            'type' => 'report',
            'date' => now()->endOfMonth()->format('Y-m-d'),
            'time' => '16:00',
            'description' => 'Umsatz- und Kundenbericht für ' . now()->format('F Y'),
            'recurring' => 'monthly',
        ]);

        return $events->sortBy('date')->values();
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|string',
            'date' => 'required|date',
            'time' => 'required|string',
            'description' => 'nullable|string',
        ]);

        // In a real application, you would store this in a calendar_events table

        return redirect()->back()->with('success', 'Termin wurde erfolgreich erstellt.');
    }
}
