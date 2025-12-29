<?php

namespace App\Modules\Calendar\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Calendar\Models\CalendarEvent;
use App\Modules\Invoice\Models\Invoice;
use App\Modules\Offer\Models\Offer;
use App\Services\ContextService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        // Custom calendar events from database
        $customEvents = CalendarEvent::forCompany($companyId)
            ->with('user:id,name')
            ->get();

        foreach ($customEvents as $event) {
            $events->push([
                'id' => $event->id,
                'title' => $event->title,
                'type' => $event->type,
                'date' => $event->date->format('Y-m-d'),
                'time' => $event->time,
                'description' => $event->description,
                'location' => $event->location,
                'user' => $event->user->name ?? null,
                'is_custom' => true,
                'calendar_event_id' => $event->id,
            ]);
        }

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
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|string|in:appointment,invoice_due,offer_expiry,report,inventory',
            'date' => 'required|date',
            'time' => 'required|string|regex:/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/',
            'description' => 'nullable|string',
            'location' => 'nullable|string|max:255',
        ]);

        $companyId = $this->getEffectiveCompanyId();
        $user = $request->user();

        CalendarEvent::create([
            'company_id' => $companyId,
            'user_id' => $user->id,
            'title' => $validated['title'],
            'type' => $validated['type'],
            'date' => $validated['date'],
            'time' => $validated['time'],
            'description' => $validated['description'] ?? null,
            'location' => $validated['location'] ?? null,
        ]);

        return redirect()->back()->with('success', 'Termin wurde erfolgreich erstellt.');
    }

    public function update(Request $request, CalendarEvent $event)
    {
        $companyId = $this->getEffectiveCompanyId();
        
        // Verify event belongs to company
        if ($event->company_id !== $companyId) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|string|in:appointment,invoice_due,offer_expiry,report,inventory',
            'date' => 'required|date',
            'time' => 'required|string|regex:/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/',
            'description' => 'nullable|string',
            'location' => 'nullable|string|max:255',
        ]);

        $event->update([
            'title' => $validated['title'],
            'type' => $validated['type'],
            'date' => $validated['date'],
            'time' => $validated['time'],
            'description' => $validated['description'] ?? null,
            'location' => $validated['location'] ?? null,
        ]);

        return redirect()->back()->with('success', 'Termin wurde erfolgreich aktualisiert.');
    }

    public function destroy(CalendarEvent $event)
    {
        $companyId = $this->getEffectiveCompanyId();
        
        // Verify event belongs to company
        if ($event->company_id !== $companyId) {
            abort(403);
        }

        $event->delete();

        return redirect()->back()->with('success', 'Termin wurde erfolgreich gelöscht.');
    }
}
