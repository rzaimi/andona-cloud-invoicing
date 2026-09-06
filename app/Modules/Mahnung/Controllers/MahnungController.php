<?php

namespace App\Modules\Mahnung\Controllers;

use App\Http\Controllers\Controller;
use App\Jobs\SendInvoiceReminder;
use App\Models\EmailLog;
use App\Modules\Company\Models\Company;
use App\Modules\Invoice\Models\Invoice;
use App\Modules\Mahnung\Services\DunningService;
use App\Services\ContextService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MahnungController extends Controller
{
    public function __construct(
        ContextService $contextService,
        protected DunningService $dunning,
    ) {
        parent::__construct($contextService);
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Invoice::class);

        $companyId = $this->getEffectiveCompanyId();
        $filter = $request->get('filter');

        $query = $this->dunning->workspaceQuery($companyId);
        if ($filter === 'paused') {
            $query->whereDate('dunning_paused_until', '>=', now()->toDateString());
        }
        if ($filter === 'failed') {
            // Query-level so pagination stays honest: latest Mahnung log failed.
            $query->whereRaw(
                "(select el.status from email_logs el where el.related_type = 'Invoice' and el.related_id = invoices.id and el.type = 'mahnung' order by el.id desc limit 1) = 'failed'"
            );
        }

        $invoices = $query
            ->paginate(20)
            ->through(function (Invoice $invoice) {
                $company = $invoice->company ?? $invoice->company()->first();
                $due = $company ? $this->dunning->nextDueLevel($invoice, $company) : null;

                return [
                    'id' => $invoice->id,
                    'number' => $invoice->number,
                    'status' => $invoice->status,
                    'due_date' => $invoice->due_date?->toDateString(),
                    'total' => $invoice->total,
                    'reminder_level' => $invoice->reminder_level,
                    'reminder_level_name' => $invoice->reminder_level_name,
                    'reminder_fee' => $invoice->reminder_fee,
                    'last_reminder_sent_at' => $invoice->last_reminder_sent_at,
                    'days_overdue' => $invoice->getDaysOverdue(),
                    'dunning_paused_until' => $invoice->dunning_paused_until?->toDateString(),
                    'is_paused' => $this->dunning->isPaused($invoice),
                    'can_send_next' => $canSend = $this->dunning->canSendManually($invoice),
                    'next_level_name' => $canSend
                        ? $invoice->getReminderLevelNameForLevel($invoice->getNextReminderLevel())
                        : null,
                    'next_auto_due' => $due !== null,
                    'customer' => $invoice->customer
                        ? ['id' => $invoice->customer->id, 'name' => $invoice->customer->name, 'email' => $invoice->customer->email]
                        : null,
                ];
            })
            ->withQueryString();

        // Latest Mahnung email per listed invoice — a failed most-recent send
        // must be visible (Zugang der Mahnung is legally relevant). Two bounded
        // queries: max(id) per invoice on this page, then those rows.
        $invoiceIds = collect($invoices->items())->pluck('id');
        $latestLogIds = EmailLog::forCompany($companyId)
            ->where('type', 'mahnung')
            ->where('related_type', 'Invoice')
            ->whereIn('related_id', $invoiceIds)
            ->selectRaw('max(id) as id')
            ->groupBy('related_id')
            ->pluck('id');
        $latestLogs = EmailLog::whereIn('id', $latestLogIds)->get()->keyBy('related_id');

        $invoices->through(function (array $row) use ($latestLogs) {
            $log = $latestLogs->get($row['id']);
            $row['last_send_failed'] = $log?->status === 'failed';
            $row['last_send_error'] = $log?->status === 'failed' ? $log->error_message : null;

            return $row;
        });

        return Inertia::render('dunning/index', [
            'invoices' => $invoices,
            'due_count' => $this->dunning->countDueForNextStep($companyId),
            'filter' => $filter,
            'stats' => $this->getDashboardStats(),
        ]);
    }

    /**
     * Bulk trigger for the approval workflow: queue every invoice whose next
     * Mahnstufe is due. Works regardless of the auto-send toggle, since this
     * IS the manual approval.
     */
    public function sendDue(Request $request)
    {
        $this->authorize('viewAny', Invoice::class);

        $companyId = $this->getEffectiveCompanyId();
        $company = Company::findOrFail($companyId);

        // Same ability as every single-invoice send action — a user who may
        // not send one Mahnung must not be able to send them all.
        $due = $this->dunning->invoicesDueForEscalation($company, requireAutoSend: false)
            ->filter(fn (Invoice $invoice) => $request->user()->can('sendReminder', $invoice));

        foreach ($due as $invoice) {
            SendInvoiceReminder::dispatch($invoice->id);
        }

        if ($due->isEmpty()) {
            return redirect()->back()->with('error', 'Keine fälligen Mahnungen vorhanden.');
        }

        // ShouldBeUnique may drop dispatches that are already queued — phrase
        // the count as an upper bound rather than a delivery promise.
        return redirect()->back()->with(
            'success',
            $due->count().' fällige Mahnung(en) zum Versand eingeplant. Bereits eingeplante Rechnungen werden nicht doppelt versendet.'
        );
    }

    public function pause(Request $request, Invoice $invoice)
    {
        $this->authorize('sendReminder', $invoice);

        $validated = $request->validate([
            'until' => 'required|date|after:today',
        ], [
            'until.after' => 'Das Datum muss in der Zukunft liegen.',
        ]);

        $until = Carbon::parse($validated['until']);
        $this->dunning->pause($invoice, $until);

        return redirect()->back()->with('success', 'Mahnlauf pausiert bis '.$until->format('d.m.Y').'.');
    }

    public function resume(Request $request, Invoice $invoice)
    {
        $this->authorize('sendReminder', $invoice);

        $this->dunning->resume($invoice);

        return redirect()->back()->with('success', 'Mahnlauf wird fortgesetzt.');
    }

    /**
     * Inkasso-Übergabedossier als ZIP herunterladen.
     */
    public function dossier(Invoice $invoice)
    {
        $this->authorize('view', $invoice);

        $path = $this->dunning->buildDossier($invoice);

        return response()->download(
            $path,
            'Inkasso-Dossier_'.$invoice->number.'.zip',
            ['Content-Type' => 'application/zip']
        )->deleteFileAfterSend(true);
    }

    public function show(Invoice $invoice)
    {
        $this->authorize('view', $invoice);

        $invoice->load(['customer:id,name,email', 'company']);

        return Inertia::render('dunning/show', [
            'invoice' => [
                'id' => $invoice->id,
                'number' => $invoice->number,
                'status' => $invoice->status,
                'due_date' => $invoice->due_date?->toDateString(),
                'total' => $invoice->total,
                'customer' => $invoice->customer
                    ? ['id' => $invoice->customer->id, 'name' => $invoice->customer->name, 'email' => $invoice->customer->email]
                    : null,
            ],
            'dunning' => $this->dunning->historyPayload($invoice),
        ]);
    }

    public function store(Request $request, Invoice $invoice)
    {
        $this->authorize('sendReminder', $invoice);

        $result = $this->dunning->sendNext($invoice, respectThresholds: false);

        if (! $result['ok']) {
            return redirect()->back()->with('error', $result['error']);
        }

        return redirect()->back()->with('success', $result['level_name'].' wurde erfolgreich versendet.');
    }
}
