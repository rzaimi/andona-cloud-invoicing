<?php

namespace App\Modules\Mahnung\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Invoice\Models\Invoice;
use App\Modules\Mahnung\Services\DunningService;
use App\Services\ContextService;
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
        $invoices = $this->dunning->workspaceQuery($companyId)
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
                    'can_send_next' => $this->dunning->canSendManually($invoice),
                    'next_level_name' => $this->dunning->canSendManually($invoice)
                        ? $invoice->getReminderLevelNameForLevel($invoice->getNextReminderLevel())
                        : null,
                    'next_auto_due' => $due !== null,
                    'customer' => $invoice->customer
                        ? ['id' => $invoice->customer->id, 'name' => $invoice->customer->name, 'email' => $invoice->customer->email]
                        : null,
                ];
            })
            ->withQueryString();

        return Inertia::render('mahnungen/index', [
            'invoices' => $invoices,
            'due_count' => $this->dunning->countDueForNextStep($companyId),
            'stats' => $this->getDashboardStats(),
        ]);
    }

    public function show(Invoice $invoice)
    {
        $this->authorize('view', $invoice);

        $invoice->load(['customer:id,name,email', 'company']);

        return Inertia::render('mahnungen/show', [
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
