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
        $companyId = $this->getEffectiveCompanyId();

        return Inertia::render('calendar/index', [
            'events' => $this->getCalendarEvents($companyId),
        ]);
    }

    private function getCalendarEvents($companyId)
    {
        $events     = collect();
        $rangeStart = now()->subDays(30)->startOfDay();
        $rangeEnd   = now()->addDays(90)->endOfDay();

        // Custom calendar events — load 4-month window around today
        $customEvents = CalendarEvent::forCompany($companyId)
            ->whereBetween('date', [$rangeStart->toDateString(), $rangeEnd->toDateString()])
            ->with('user:id,name')
            ->get();

        foreach ($customEvents as $event) {
            $events->push([
                'id'                => $event->id,
                'title'             => $event->title,
                'type'              => $event->type,
                'date'              => $event->date->format('Y-m-d'),
                'time'              => $event->time,
                'description'       => $event->description,
                'location'          => $event->location,
                'user'              => $event->user->name ?? null,
                'is_custom'         => true,
                'calendar_event_id' => $event->id,
            ]);
        }

        // Invoice due dates — only sent/overdue invoices, within date range
        $invoices = Invoice::where('company_id', $companyId)
            ->whereIn('status', ['sent', 'overdue'])
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [$rangeStart->toDateString(), $rangeEnd->toDateString()])
            ->with('customer:id,name')
            ->select('id', 'number', 'due_date', 'total', 'status', 'customer_id')
            ->get();

        foreach ($invoices as $invoice) {
            $events->push([
                'id'          => 'invoice_' . $invoice->id,
                'title'       => "Rechnung {$invoice->number} fällig",
                'type'        => 'invoice_due',
                'date'        => $invoice->due_date->format('Y-m-d'),
                'time'        => '09:00',
                'customer'    => $invoice->customer->name ?? 'Unbekannt',
                'amount'      => (float) ($invoice->total ?? 0),
                'status'      => $invoice->due_date->isPast() ? 'overdue' : 'pending',
                'description' => 'Zahlungserinnerung senden',
                'invoice_id'  => $invoice->id,
            ]);
        }

        // Offer expiry dates — only sent offers, within date range
        $offers = Offer::where('company_id', $companyId)
            ->where('status', 'sent')
            ->whereNotNull('valid_until')
            ->whereBetween('valid_until', [$rangeStart->toDateString(), $rangeEnd->toDateString()])
            ->with('customer:id,name')
            ->select('id', 'number', 'valid_until', 'total', 'status', 'customer_id')
            ->get();

        foreach ($offers as $offer) {
            $daysUntilExpiry = now()->diffInDays($offer->valid_until, false);
            $events->push([
                'id'          => 'offer_' . $offer->id,
                'title'       => "Angebot {$offer->number} läuft ab",
                'type'        => 'offer_expiry',
                'date'        => $offer->valid_until->format('Y-m-d'),
                'time'        => '23:59',
                'customer'    => $offer->customer->name ?? 'Unbekannt',
                'amount'      => (float) ($offer->total ?? 0),
                'status'      => $daysUntilExpiry < 0 ? 'expired' : ($daysUntilExpiry <= 3 ? 'expiring' : 'active'),
                'description' => 'Angebot verlängern oder nachfassen',
                'offer_id'    => $offer->id,
            ]);
        }

        // Recurring: end-of-month report reminder
        $events->push([
            'id'          => 'monthly_report_' . now()->format('Y_m'),
            'title'       => 'Monatsbericht erstellen',
            'type'        => 'report',
            'date'        => now()->endOfMonth()->format('Y-m-d'),
            'time'        => '16:00',
            'description' => 'Umsatz- und Kundenbericht für ' . now()->translatedFormat('F Y'),
            'recurring'   => 'monthly',
        ]);

        return $events->sortBy('date')->values();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|string|in:appointment,invoice_due,offer_expiry,report,inventory',
            'date' => 'required|date',
            'time' => ['required', 'string', 'regex:/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/'],
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
        $this->authorize('update', $event);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|string|in:appointment,invoice_due,offer_expiry,report,inventory',
            'date' => 'required|date',
            'time' => ['required', 'string', 'regex:/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/'],
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
        $this->authorize('delete', $event);

        $event->delete();

        return redirect()->back()->with('success', 'Termin wurde erfolgreich gelöscht.');
    }
}
